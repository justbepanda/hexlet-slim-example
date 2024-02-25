<?php

namespace Mof\HexletSlimExample;

class Validator
{
    public function validate($user): array
    {
        $errors = [];
        if(empty($user['nickname'])) {
            $errors['nickname'] = 'Заполните nickname';
        }
        if(empty($user['email'])) {
            $errors['email'] = 'Заполните email';
        }
        return $errors;
    }
}