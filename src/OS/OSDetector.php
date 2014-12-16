<?php
/**
 * Created by PhpStorm.
 * User: Estevao
 * Date: 14-12-2014
 * Time: 22:29
 */

namespace Tivie\Command\OS;

require_once(__DIR__ . '/../namespace.constants.php');

/**
 * Class OSDetector
 * @package Tivie\Command\OS
 */
class OSDetector implements OSDetectorInterface
{
    private static $os;

    /**
     * Detect the OS this server is running on
     *
     * @return OS
     */
    public function detect()
    {
        if (is_null(self::$os)) {
            $os = new OS();
            $os->name = strtoupper(PHP_OS);
            self::$os = $this->parsePHPOS($os);
        }
        return self::$os;
    }

    private function parsePHPOS($os)
    {
        switch ($os->name) {

            case "WINDOWS":
            case "WINNT":
            case "WIN32":
            case "INTERIX":
            case "UWIN":
            case "UWIN-W7":
                $os->family = WINDOWS_FAMILY;
                $os->def = WINDOWS;
                break;

            case "DARWIN":
                $os->family = UNIX_FAMILY;
                $os->def = MACOSX;
                break;

            case "LINUX":
            case "GNU":
                $os->family = UNIX_FAMILY;
                $os->def = LINUX;
                break;

            case "AIX":
                $os->family = UNIX_FAMILY;
                $os->def = AIX;
                break;

            case "CYGWIN_NT-5.1":
            case "CYGWIN_NT-6.1":
            case "CYGWIN_NT-6.1-WOW64":
                $os->family = WINDOWS_FAMILY;
                $os->def = CYGWIN_NT;
                break;

            case "MINGW32_NT-6.1":
            case "MSYS_NT-6.1":
                $os->family = WINDOWS_FAMILY;
                $os->def = MSYS;
                break;

            case "DRAGONFLY":
            case "OPENBSD":
            case "FREEBSD":
            case "NETBSD":
            case "GNU/KFREEBSD":
            case "GNU/FREEBSD":
            case "DEBIAN/FREEBSD":
                $os->family = UNIX_FAMILY;
                $os->def = BSD;
                break;

            case "MINIX":
            case "IRIX":
            case "IRIX64":
            case "HP-UX":
            case "OSF1":
            case "SCO_SV":
            case "ULTRIX":
            case "RELIANTUNIX-Y":
            case "SINIX-Y":
            case "UNIXWARE":
            case "SN5176":
                $os->family = UNIX_FAMILY;
                $os->def = GEN_UNIX;
                break;

            case "QNX":
                $os->family = UNIX_FAMILY;
                $os->def = QNX;
                break;

            case "SUNOS":
                $os->family = UNIX_FAMILY;
                $os->def = SUN_OS;
                break;

            case "BEOS":
            case "BE_OS":
            case "HAIKU":
                $os->family = OTHER_FAMILY;
                $os->def    = BE_OS;
                break;

            default:
            case "NONSTOP_KERNEL":
            case "OS390":
            case "OS/390":
            case "OS400":
            case "OS/400":
                $os->family = 0;
                $os->def = 0;
                break;
        }
        return $os;
    }
}