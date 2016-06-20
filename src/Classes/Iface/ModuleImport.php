<?php

namespace FinXLog\Iface;

interface ModuleImport
{
    /**
     * @param null|int $limit
     * @return $this;
     */
    public function run($limit = null);
}