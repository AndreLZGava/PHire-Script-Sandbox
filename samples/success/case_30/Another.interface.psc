<?php


namespace PHireScript\Sandbox\samples\success\case_30;


interface Another
{
public function save(Array $data): bool;

public function delete(): void;

public function getCompleteUserName(): string|null;

}

