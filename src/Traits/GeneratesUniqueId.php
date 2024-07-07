<?php

namespace OramaCloud\Traits;

trait GeneratesUniqueId
{
    public function generateUniqueId($prefix = '')
    {
        return substr(str_replace('.', '', uniqid($prefix, true) . str_shuffle('abcdefghjkmnpqrstvwxyz0123456789')), 0, 24);
    }
}