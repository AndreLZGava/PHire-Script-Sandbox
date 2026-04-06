<?php


namespace PHireScript\Classes;


use PHireScript\Classes\Repository;

use PHireScript\Classes\User;

 class UserRepository extends Repository {
    public string $tableName = 'user';
    public function getUser( $id): null|self {
            //return this.database.get(this.tableName).first(id: id)

    }

}

