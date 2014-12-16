<?php
/**
 * Created by PhpStorm.
 * User: Estevao
 * Date: 14-12-2014
 * Time: 19:45
 */

namespace Tivie\Command\OS;

require_once(__DIR__ . '/../namespace.constants.php');

interface OSDetectorInterface
{
    /**
     * Detect the OS this server is running on
     *
     * @return OS
     */
    public function detect();
}