<?php

namespace AnonyChat\Server;

use AnonyChat\Tools\StringNormalizer;

class DecodeData
{
    public static function decode(string $data): array
    {
        if (!empty($data)) {
            $data = json_decode($data, true);
            if (
                !empty($data)
                && !empty($data['type'])
                && !empty($data['room'])
                && !empty($data['text'])
            ) {
                $data['room'] = StringNormalizer::room($data['room']);
                $data['type'] = StringNormalizer::type($data['type']);
                $data['text'] = StringNormalizer::text($data['text']);
            }
            return $data;
        }
        return [];
    }

}
