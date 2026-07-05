<?php


use PHireScript\Runtime\Types\SuperTypes\Email;
use PHireScript\Runtime\Types\SuperTypes\Ipv4;
use PHireScript\Runtime\Types\SuperTypes\Ipv6;
use PHireScript\Runtime\Types\SuperTypes\Uuid;
use PHireScript\Runtime\Types\SuperTypes\Color;
use PHireScript\Runtime\Types\SuperTypes\Url;
use PHireScript\Runtime\Types\SuperTypes\Cron;
use PHireScript\Runtime\Types\SuperTypes\Duration;
use PHireScript\Runtime\Types\SuperTypes\Json;
use PHireScript\Runtime\Types\SuperTypes\Mac;
use PHireScript\Runtime\Types\SuperTypes\Slug;

    // this is a inline comment

    /**
*  This is a multiple line comment
*/

$thisIsString = 'teste';
$email = Email::cast('andrelzgava@gmail.com');
$ipv4 = Ipv4::cast('127.0.0.1');
$ipv6 = Ipv6::cast('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
$generated = Uuid::cast();
$uuidToValidate = Uuid::cast('550E8400-E29B-41D4-A716-446655440000');
$color = Color::cast('#fff');
$url = Url::cast('www.google.com');
$cron = Cron::cast('@today');
$duration = Duration::cast('5m');
$json = Json::cast('{"name":"John","age":30}');
$mac = Mac::cast('00:1a:2b:3c:4d:5e');
$slug = Slug::cast('hello World');
    //thisISBool = true

    //thisIsNumber = 12.5

    //thisIsArray = ['test' => ['another' => 'test']]

    //thisIsAnEmptyObject = {}

    //thisIsAnObject = {id: 10}

    //oneVariable = thisIsAnObject

    // does not work

    //thisIsExpression  = 10 - 5 / thisIsNumber

    // does not work

    //anotherTest = String(10)

