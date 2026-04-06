<?php

declare(strict_types=1);

namespace PHireScript\Runtime\CustomClasses;

use PHireScript\Runtime\CustomClasses\MagicBaseMethods;
use PHireScript\Runtime\DefaultOverrideMethods\BaseParams;

class MagicMethods
{
    public function onCreate()
    {
        return new MagicBaseMethods(
            name: 'onCreate',
            related: '__construct',
            return: ['Void'],
            params: [
                new BaseParams(name: '@params', type: 'mixed', required: false)
            ]
        );
    }

    public function onDestroy()
    {
        return new MagicBaseMethods(
            name: 'onDestroy',
            related: '__destruct',
            return: ['Void'],
        );
    }

    public function onGet()
    {
        return new MagicBaseMethods(
            name: 'onGet',
            related: '__get',
            return: ['Mixed'],
            params: [
                new BaseParams(name: '@property', type: 'string', required: true)
            ]
        );
    }

    public function onSet()
    {
        return new MagicBaseMethods(
            name: 'onSet',
            related: '__set',
            return: ['Void'],
            params: [
                new BaseParams(name: '@property', type: 'string', required: true),
                new BaseParams(name: '@value', type: 'mixed', required: true)
            ]
        );
    }

    public function hasHas()
    {
        return new MagicBaseMethods(
            name: 'hasHas',
            related: '__isset',
            return: ['Bool'],
            params: [
                new BaseParams(name: '@property', type: 'string', required: true),
            ]
        );
    }

    public function onUnset()
    {
        return new MagicBaseMethods(
            name: 'onUnset',
            related: '__unset',
            return: ['Void'],
            params: [
                new BaseParams(name: '@property', type: 'string', required: true),
            ]
        );
    }

    public function onCall()
    {
        return new MagicBaseMethods(
            name: 'onCall',
            related: '__call',
            return: ['Mixed'],
            params: [
                new BaseParams(name: '@method', type: 'string', required: true),
                new BaseParams(name: '@arguments', type: 'array', required: true),
            ]
        );
    }

    public function onStaticCall()
    {
        return new MagicBaseMethods(
            name: 'onStaticCall',
            related: '__callStatic',
            return: ['Mixed'],
            params: [
                new BaseParams(name: '@method', type: 'string', required: true),
                new BaseParams(name: '@arguments', type: 'array', required: true),
            ]
        );
    }

    public function toString()
    {
        return new MagicBaseMethods(
            name: 'toString',
            related: '__toString',
            return: ['String'],
        );
    }

    public function toSerialize()
    {
        return new MagicBaseMethods(
            name: 'toSerialize',
            related: '__serialize',
            return: ['Array'],
        );
    }

    public function toUnserialize()
    {
        return new MagicBaseMethods(
            name: 'toUnserialize',
            related: '__unserialize',
            return: ['Void'],
            params: [
                new BaseParams(name: '@data', type: 'array', required: true)
            ]
        );
    }

    public function beforeSerialize()
    {
        return new MagicBaseMethods(
            name: 'beforeSerialize',
            related: '__sleep',
            return: ['Array'],
        );
    }

    public function afterUnserialize()
    {
        return new MagicBaseMethods(
            name: 'afterUnserialize',
            related: '__wakeup',
            return: ['Void'],
        );
    }

    public function onClone()
    {
        return new MagicBaseMethods(
            name: 'onClone',
            related: '__clone',
            return: ['Void'],
        );
    }

    public function toInspect()
    {
        return new MagicBaseMethods(
            name: 'toInspect',
            related: '__debugInfo',
            return: ['Array'],
        );
    }
}
