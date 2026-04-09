<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Managers\Context;

enum Context: String
{
    case ClassType = 'class';
    case Interface = 'interface';
    case Trait = 'trait';
    case Type = 'type';
    case Method = 'method';
    case Immutable = 'immutable';
    case Params = 'params';
    case Static = 'static';
    case Global = 'global';
    case Variable = 'variable';
    case Queue = 'queue';
    case Stack = 'stack';
    case Map = 'map';
    case List = 'list';
    case ExplicitlyTyped = 'explicitly typed';
    case Assignment = 'assignment';
    case Casting = 'casting';
    case Bool = 'bool';
    case ArrayLiteral = 'array literal';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
