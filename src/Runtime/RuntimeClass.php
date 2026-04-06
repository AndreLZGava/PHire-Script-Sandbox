<?php

declare(strict_types=1);

namespace PHireScript\Runtime;

class RuntimeClass
{
    public const CONTEXT_GET_ARGUMENTS = 'arguments';

    public const CONTEXT_GENERAL = 'general';

    public const CONTEXT_PRE_BUILD = 'pre';

    public const CONTEXT_GET_BODY_METHOD = 'method';

    public const OBJECT_AS_CLASS = ['type', 'class', 'interface', 'trait', 'enum', 'immutable'];

    public const START_END_ARGUMENTS = ['(', ')'];

    public const ACCESSORS = ['*', '#', '+'];

    public const START_BLOCK = '{';

    public const END_BLOCK = '}';

    public const BLOCK_DELIMITERS = [self::START_BLOCK, self::END_BLOCK];

    public const CHARACTERS_ON_METHODS = ['!', '?', ':'];

    public const GETTER_AND_SETTER = ['<', '>'];

    public const KEYWORD_PACKAGE = 'pkg';

    public const DEFAULT_FILE_EXTENSION = 'ps'; // 'phs'

    public const DEFAULT_FILE_TEST_EXTENSION = 'pst';

    public const DEFAULT_FILE_SNAPSHOT_EXTENSION = 'psc';
}
