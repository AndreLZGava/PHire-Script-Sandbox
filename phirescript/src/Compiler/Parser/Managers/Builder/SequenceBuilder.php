<?php

namespace PHireScript\Compiler\Parser\Managers\Builder;

use PHireScript\Compiler\Parser\Managers\TokenManager;
use PHireScript\Helper\Debug\Debug;

class SequenceBuilder {
  private array $rules = [];
  private $untilCallback = null;
  private int $direction = 1;
  private bool $debug = false;

  private const MAX_FORWARD = 20;
  private const MAX_BACKWARD = 10;

  public function __construct(
    private TokenManager $tokenManager
  ) {
  }

  public function lookAhead(): self {
    $this->direction = 1;
    return $this;
  }

  public function lookBehind(): self {
    $this->direction = -1;
    return $this;
  }

  public function once(callable $match): self {
    $this->rules[] = [
      'type' => 'once',
      'match' => $match,
    ];

    return $this;
  }

  public function then(callable $match): self {
    return $this->once($match);
  }

  public function separated(callable $match, callable $separator): self {
    $this->rules[] = [
      'type' => 'separated',
      'match' => $match,
      'separator' => $separator,
    ];

    return $this;
  }

  public function test(callable $builderFn, int $offset = 0): bool {
    $sub = $this->spawnSubBuilder($builderFn, $offset);
    return $sub['matched'];
  }

  public function optional(callable $builderFn): self {
    $this->rules[] = [
      'type' => 'optional',
      'builder' => $builderFn,
    ];

    return $this;
  }

  public function skipUntil(callable $callback): self {
    $this->rules[] = [
      'type' => 'skip_until',
      'callback' => $callback,
    ];

    return $this;
  }

  public function or(callable ...$builders): self {
    $this->rules[] = [
      'type' => 'or',
      'builders' => $builders,
    ];

    return $this;
  }

  public function around(callable $backward, callable $forward): self {
    $this->rules[] = [
      'type' => 'around',
      'backward' => $backward,
      'forward' => $forward,
    ];

    return $this;
  }

  public function group(callable $builderFn): self {
    $this->rules[] = [
      'type' => 'group',
      'builder' => $builderFn,
    ];

    return $this;
  }

  public function until(callable $callback): self {
    $this->untilCallback = $callback;
    return $this;
  }

  private function spawnDirectionalSub(callable $builderFn, int $direction, int $startOffset): array {
    $subBuilder = new self($this->tokenManager);
    $subBuilder->direction = $direction;
    $subBuilder->untilCallback = $this->untilCallback;

    $builderFn($subBuilder);

    return $this->executeSub($subBuilder, $startOffset);
  }

  public function match(): bool {
    $offset = 0;
    $steps = 0;
    $limit = $this->direction === 1
      ? self::MAX_FORWARD
      : self::MAX_BACKWARD;

    foreach ($this->rules as $rule) {
      if ($this->debug) {
        $token = $this->peek($offset);
        Debug::show("[DEBUG] Rule: {$rule['type']} | Token: " . ($token->value ?? 'null') . " | Offset: {$offset}");
      }

      if ($steps >= $limit) {
        return false;
      }

      switch ($rule['type']) {
        case 'skip_until':
          while (true) {
            if ($steps >= $limit) {
              return false;
            }

            $token = $this->peek($offset);

            if ($rule['callback']($token)) {
              break;
            }

            $offset += $this->direction;
            $steps++;
          }
          break;
        case 'around':
          $back = $this->spawnDirectionalSub($rule['backward'], -1, 0);
          if (!$back['matched']) {
            return false;
          }

          $forward = $this->spawnDirectionalSub($rule['forward'], 1, 0);
          if (!$forward['matched']) {
            return false;
          }

          break;

        case 'once':
          $token = $this->peek($offset);

          if ($this->shouldStop($token) || !$rule['match']($token)) {
            return false;
          }

          $offset += $this->direction;
          $steps++;
          break;

        case 'separated':
          $matched = false;

          while (true) {
            if ($steps >= $limit) {
              return false;
            }

            $token = $this->peek($offset);

            if ($this->shouldStop($token)) {
              break;
            }

            if (!$rule['match']($token)) {
              break;
            }

            $matched = true;
            $offset += $this->direction;
            $steps++;

            $separatorToken = $this->peek($offset);

            if ($rule['separator']($separatorToken)) {
              $offset += $this->direction;
              $steps++;
              continue;
            }

            break;
          }

          if (!$matched) {
            return false;
          }
          break;

        case 'optional':
          $sub = $this->spawnSubBuilder($rule['builder'], $offset);
          if ($sub['matched']) {
            $offset = $sub['finalOffset'];
            $steps += $sub['consumed'];
          }
          break;

        case 'group':
          $sub = $this->spawnSubBuilder($rule['builder'], $offset);

          if (!$sub['matched']) {
            return false;
          }

          $offset = $sub['finalOffset'];
          $steps += $sub['consumed'];
          break;

        case 'or':
          $matched = false;

          foreach ($rule['builders'] as $builderFn) {
            $sub = $this->spawnSubBuilder($builderFn, $offset);

            if ($sub['matched']) {
              $offset = $sub['finalOffset'];
              $steps += $sub['consumed'];
              $matched = true;
              break;
            }
          }

          if (!$matched) {
            return false;
          }
          break;
      }
    }

    return true;
  }

  public function debug(): self {
    $this->debug = true;
    return $this;
  }

  private function spawnSubBuilder(callable $builderFn, int $startOffset): array {
    $subBuilder = new self($this->tokenManager);
    $subBuilder->direction = $this->direction;
    $subBuilder->untilCallback = $this->untilCallback;

    $builderFn($subBuilder);

    return $this->executeSub($subBuilder, $startOffset);
  }

  private function executeSub(self $builder, int $startOffset): array {
    $offset = $startOffset;
    $steps = 0;

    $limit = $builder->direction === 1
      ? self::MAX_FORWARD
      : self::MAX_BACKWARD;

    foreach ($builder->rules as $rule) {
      if ($this->debug) {
        $token = $this->peek($offset);
        Debug::show("[DEBUG] Rule: {$rule['type']} | Token: " . ($token->value ?? 'null') . " | Offset: {$offset}");
      }

      if ($steps >= $limit) {
        return ['matched' => false, 'consumed' => 0, 'finalOffset' => $startOffset];
      }

      switch ($rule['type']) {

        case 'once':
          $token = $this->peek($offset);

          if ($builder->shouldStop($token) || !$rule['match']($token)) {
            return ['matched' => false, 'consumed' => 0, 'finalOffset' => $startOffset];
          }

          $offset += $builder->direction;
          $steps++;
          break;

        case 'separated':
          $matched = false;

          while (true) {
            if ($steps >= $limit) {
              return ['matched' => false, 'consumed' => 0, 'finalOffset' => $startOffset];
            }

            $token = $this->peek($offset);

            if ($builder->shouldStop($token)) {
              break;
            }

            if (is_callable($rule['match'])) {
              if (!$rule['match']($token)) {
                break;
              }

              $offset += $builder->direction;
              $steps++;
            } else {
              // é um builder (group)
              $sub = $this->executeSub(
                $this->buildSub($rule['match'], $builder),
                $offset
              );

              if (!$sub['matched']) {
                break;
              }

              $offset = $sub['finalOffset'];
              $steps += $sub['consumed'];
            }

            $matched = true;
            $offset += $builder->direction;
            $steps++;

            $separatorToken = $this->peek($offset);

            if ($rule['separator']($separatorToken)) {
              $offset += $builder->direction;
              $steps++;
              continue;
            }

            break;
          }

          if (!$matched) {
            return ['matched' => false, 'consumed' => 0, 'finalOffset' => $startOffset];
          }

          break;

        case 'optional':
          $sub = $this->executeSub(
            $this->buildSub($rule['builder'], $builder),
            $offset
          );

          if ($sub['matched']) {
            $offset = $sub['finalOffset'];
            $steps += $sub['consumed'];
          }
          break;

        case 'group':
          $sub = $this->executeSub(
            $this->buildSub($rule['builder'], $builder),
            $offset
          );

          if (!$sub['matched']) {
            return ['matched' => false, 'consumed' => 0, 'finalOffset' => $startOffset];
          }

          $offset = $sub['finalOffset'];
          $steps += $sub['consumed'];
          break;

        case 'or':
          $matched = false;

          foreach ($rule['builders'] as $builderFn) {
            $sub = $this->executeSub(
              $this->buildSub($builderFn, $builder),
              $offset
            );

            if ($sub['matched']) {
              $offset = $sub['finalOffset'];
              $steps += $sub['consumed'];
              $matched = true;
              break;
            }
          }

          if (!$matched) {
            return ['matched' => false, 'consumed' => 0, 'finalOffset' => $startOffset];
          }

          break;
      }
    }

    return ['matched' => true, 'consumed' => $steps, 'finalOffset' => $offset];
  }

  private function buildSub(callable $builderFn, self $parent): self {
    $subBuilder = new self($this->tokenManager);
    $subBuilder->direction = $parent->direction;
    $subBuilder->untilCallback = $parent->untilCallback;

    $builderFn($subBuilder);

    return $subBuilder;
  }

  private function peek(int $offset) {
    return $this->tokenManager->peek($offset);
  }

  private function shouldStop($token): bool {
    return $this->untilCallback && ($this->untilCallback)($token);
  }
}
