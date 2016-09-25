<?php
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Message;
use Phalcon\Validation;
use Phalcon\Validation\Validator\InclusionIn;
use Phalcon\Validation\Validator\Uniqueness;

class Robots extends Model
{
    public function validation()
    {
        $validator = new Validation();

        $validator->add(
            'type', //your field name
            new InclusionIn([
                "field"  => "type",
                "domain" => [
                    "droid",
                    "mechanical",
                    "virtual",
                ]
            ])
        );


        $validator->add(
            'name',
            new Uniqueness([
                "field"   => "name",
                "message" => "The robot name must be unique",
            ])
        );

        // Year cannot be less than zero
        if ($this->year < 0) {
            $this->appendMessage(
                new Message("The year cannot be less than zero")
            );
        }


        return $this->validate($validator);

    }
}