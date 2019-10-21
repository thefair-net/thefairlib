<?php
/**
 * Prober.php
 * 浏览器类型探针
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\BigPipe;

class Prober
{
    public static $user_agent = '';
    public static $user_agent_conf = array(

        'platform' => array(
            'windows nt 6.1' => 'Windows 7',
            'windows nt 6.0' => 'Windows Vista',
            'windows nt 5.2' => 'Windows 2003',
            'windows nt 5.1' => 'Windows XP',
            'windows nt 5.0' => 'Windows 2000',
            'windows nt 4.0' => 'Windows NT',
            'winnt4.0'       => 'Windows NT',
            'winnt 4.0'      => 'Windows NT',
            'winnt'          => 'Windows NT',
            'windows 98'     => 'Windows 98',
            'win98'          => 'Windows 98',
            'windows 95'     => 'Windows 95',
            'win95'          => 'Windows 95',
            'windows'        => 'Unknown Windows OS',
            'os x'           => 'Mac OS X',
            'intel mac'      => 'Intel Mac',
            'ppc mac'        => 'PowerPC Mac',
            'powerpc'        => 'PowerPC',
            'ppc'            => 'PowerPC',
            'cygwin'         => 'Cygwin',
            'linux'          => 'Linux',
            'debian'         => 'Debian',
            'openvms'        => 'OpenVMS',
            'sunos'          => 'Sun Solaris',
            'amiga'          => 'Amiga',
            'beos'           => 'BeOS',
            'apachebench'    => 'ApacheBench',
            'freebsd'        => 'FreeBSD',
            'netbsd'         => 'NetBSD',
            'bsdi'           => 'BSDi',
            'openbsd'        => 'OpenBSD',
            'os/2'           => 'OS/2',
            'warp'           => 'OS/2',
            'aix'            => 'AIX',
            'irix'           => 'Irix',
            'osf'            => 'DEC OSF',
            'hp-ux'          => 'HP-UX',
            'hurd'           => 'GNU/Hurd',
            'unix'           => 'Unknown Unix OS',
        ),

        'browser' => array(
            'UC'         		=> 'UCBrowser',
            'Opera'             => 'Opera',
            'MSIE'              => 'Internet Explorer',
            'Internet Explorer' => 'Internet Explorer',
            'Firefox'           => 'Firefox',
            'Chrome'            => 'Chrome',
            'Safari'            => 'Safari',
            'CFNetwork'         => 'Safari', // Core Foundation for OSX, WebKit/Safari
        ),

        'mobile' => array(
            'mobileexplorer' => 'Mobile Explorer',
            'openwave'       => 'Open Wave',
            'opera mini'     => 'Opera Mini',
            'operamini'      => 'Opera Mini',
            'elaine'         => 'Palm',
            'palmsource'     => 'Palm',
            'digital paths'  => 'Palm',
            'avantgo'        => 'Avantgo',
            'xiino'          => 'Xiino',
            'palmscape'      => 'Palmscape',
            'nokia'          => 'Nokia',
            'ericsson'       => 'Ericsson',
            'blackBerry'     => 'BlackBerry',
            'motorola'       => 'Motorola',
            'iphone'         => 'iPhone',
            'android'        => 'Android',
            'Mobile'         => 'Unknown Mobile',
        ),

        'robot' => array(
            'Baiduspider'   => 'Baiduspider',
            'Googlebot'   	=> 'Googlebot',
            '360Spider'   	=> '360Spider',//"Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.8.0.11) Gecko/20070312 Firefox/1.5.0.11; 360Spider"
            'Sogou'   		=> 'Sogou',
            'YodaoBot'   	=> 'YodaoBot',
            'MSNBot'      	=> 'MSNBot',
            'Soso'      	=> 'Soso',
            'Bingbot'       => 'Bingbot',
            'Yahoo'       	=> 'Yahoo',
        ),
    );
    /**
     * Returns information about the client user agent.
     *
     *     // Returns "Chrome" when using Google Chrome
     *     $browser = Request::user_agent('browser');
     *
     * Multiple values can be returned at once by using an array:
     *
     *     // Get the browser and platform with a single call
     *     $info = Kohana_Request::user_agent(array('browser', 'platform'));
     *
     * When using an array for the value, an associative array will be returned.
     *
     * @param   mixed   string to return: browser, version, robot, mobile, platform; or array of values
     * @return  mixed   requested information, FALSE if nothing is found
     */
    static function getClientAgent($value = array('robot', 'browser', 'platform', 'mobile'))
    {
        if (isset($_SERVER['HTTP_USER_AGENT']))
        {
            // Set the client user agent
            Prober::$user_agent = $_SERVER['HTTP_USER_AGENT'];
        }
        if (is_array($value))
        {
            $agent = array();
            foreach ($value as $v)
            {
                // Add each key to the set
                $agent[$v] = Prober::getClientAgent($v);
            }

            return $agent;
        }
        static $info;

        if (isset($info[$value]))
        {
            // This value has already been found
            return $info[$value];
        }

        if ($value === 'browser' OR $value == 'version')
        {
            // Load browsers
            $browsers = self::$user_agent_conf['browser'];

            foreach ($browsers as $search => $name)
            {
                if (stripos(Prober::$user_agent, $search) !== FALSE)
                {
                    // Set the browser name
                    $info['browser'] = $name;

                    if (preg_match('#'.preg_quote($search).'[^0-9.]*+([0-9.][0-9.a-z]*)#i', Prober::$user_agent, $matches))
                    {
                        // Set the version number
                        $info['version'] = $matches[1];
                    }
                    else
                    {
                        // No version number found
                        $info['version'] = FALSE;
                    }

                    return $info[$value];
                }
            }
        }
        else
        {
            // Load the search group for this type
            $group = self::$user_agent_conf[$value];
            if(!empty($group)){
                foreach ($group as $search => $name)
                {
                    if (stripos(Prober::$user_agent, $search) !== FALSE)
                    {
                        // Set the value name
                        return $info[$value] = $name;
                    }
                }
            }
        }

        // The value requested could not be found
        return $info[$value] = FALSE;
    }

}