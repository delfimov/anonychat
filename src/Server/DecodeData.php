<?php

namespace AnonyChat\Server;

use AnonyChat\Tools\StringNormilizer;

class DecodeData
{
    public static function decode($data)
    {
        if (!empty($data)) {
            $data = json_decode($data, true);
            if (
                !empty($data)
                && !empty($data['type'])
                && !empty($data['room'])
                && !empty($data['text'])
            ) {
                $data['room'] = StringNormilizer::room($data['room']);
                $data['type'] = StringNormilizer::type($data['type']);
                $data['text'] = StringNormilizer::text($data['text']);
            }
        }
        return $data;
    }

}
