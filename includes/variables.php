<?php
/* Copyright © 2014 TheHostingTool
*
* This file is part of TheHostingTool.
*
* TheHostingTool is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* TheHostingTool is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with TheHostingTool.  If not, see <http://www.gnu.org/licenses/>.
*/

//Check if called by script
if(THT != 1){die();}

if(INSTALL == 1) {
    /*
     * THT Page Generation Time
     */
    global $db, $starttime, $style, $main; #Define global, as we are going to pull up things from db
    if($db->config("show_page_gentime") == 1){
        $mtime = explode(' ', microtime());
        $totaltime = $mtime[0] + $mtime[1] - $starttime;
        $gentime = substr($totaltime, 0, 5);
        $array['PAGEGEN'] = $gentime;
        $array['IP'] = getenv('REMOTE_ADDR');
        $pagegen .= $style->replaceVar('tpl/footergen.tpl', $array);
        if($db->config("show_footer")) {
            $array2['EXTRA'] = '';
            if(!$main->canRun('shell_exec')) {
                $array2['EXTRA'] = 'Some statistics could not be provided because shell_exec has been disabled.';
            }
            $array2['OS'] = php_uname();
            $array2['DISTRO'] = '';
            if(php_uname('s') == 'Linux') {
                $distro = false;
                if($main->canRun("shell_exec")) {
                    $result = shell_exec("cat /etc/*-release");
                    if(preg_match('/DISTRIB_DESCRIPTION="(.*)"/', $result, $match)) {
                        $distro = $match[1];
                    }
                    else {
                        $distro = $result;
                    }
                }
                if($distro) {
                    $array2['DISTRO'] = '<tr><td><strong>Linux Distro:</strong></td><td> '.$distro.' </td></tr>';
                }
            }
            $array2['SOFTWARE'] = $_SERVER["SERVER_SOFTWARE"];
            $array2['PHP_VERSION'] = phpversion();
            $curlVersion = curl_version();
            $array2['CURL_TITLE'] = "Version Number: " . $curlVersion["version_number"] . "<br />Version: " . $curlVersion["version"]
            . "<br />SSL Version Number: " . $curlVersion["ssl_version_number"] . "<br />SSL Version: " . $curlVersion["ssl_version"]
            . "<br />zlib Version: " . $curlVersion["libz_version"] . "<br />Host: " . $curlVersion["host"] . "<br />Age: " . $curlVersion["age"]
            . "<br />Protocols: " . implode($curlVersion["protocols"], " ");
            $array2['CURL_VERSION'] = $curlVersion["version"];
            $array2['MYSQL_VERSION'] = '';
            $versionResult = $db->fetch_array($db->query("SELECT Version()"));
            if($versionResult[0]) {
                $array2['MYSQL_VERSION'] = '<tr><td><strong>MySQL Version:</strong></td><td> '.$versionResult[0].' </td></tr>';
            }
            $array2["SERVER"] = $_SERVER["HTTP_HOST"];
            $array['TITLE'] = $style->replaceVar('tpl/aserverstatus.tpl',$array2);
            $pagegen .= $style->replaceVar('tpl/footerdebug.tpl',$array);
        }
    }
    else {
        $pagegen = '';
    }

     if($db->config("show_version_id") == 1) {
        $version = $db->config('vname');
        $r = $main->getGitRevision();
        if($r) { $version .= " (<a target=\"_blank\" href=\"https://github.com/TheHostingTool/TheHostingTool/commit/$r\">".substr($r, 0, 10)."</a>)"; }
    }
    else {
        $version = '';
    }
    /*
     * THT Navigation
    */
    if(FOLDER != "install") {
        $navbar = $db->query("SELECT * FROM `<PRE>navbar` ORDER BY `order` ASC");
        while($data2 = $db->fetch_array($navbar)) {
            if(!$db->config("show_acp_menu") && $data2['link'] == "admin") {
                //Do something?
            }
            else {
                $array4['ID'] = "nav_". $data2['name'];
                if(PAGE == $data2['visual']) {
                    $array4['ACTIVE'] = ' class="active"';
                }
                else {
                    $array4['ACTIVE'] = '';
                }
                $array4['LINK'] = $data2['link'];
                $array4['ICON'] = $data2['icon'];
                $array4['NAME'] = $data2['visual'];
                $navbits .= $style->replaceVar("tpl/navbit.tpl", $array4);
            }
        }
    }
    $array3['NAV'] = $navbits;
    $navigation = $style->replaceVar("tpl/nav.tpl", $array3);
}

/**********************************************************************/
$data = preg_replace("/<THT TITLE>/si", NAME . " :: " . PAGE . " - " . SUB, $data);
$data = preg_replace("/<NAME>/si", NAME, $data);
$data = preg_replace("/<CSS>/si", $this->css(), $data);
$data = preg_replace("/<JAVASCRIPT>/si", $this->javascript(), $data);
$data = preg_replace("/<MENU>/si", $navigation, $data);
$data = preg_replace("/<URL>/si", URL, $data);
$data = preg_replace("/<AJAX>/si", URL."includes/ajax.php", $data);
$data = preg_replace("/<IMG>/si", URL . "themes/". THEME ."/images/", $data);
$data = preg_replace("/<PAGEGEN>/si", $pagegen, $data); #Page Generation Time
$data = preg_replace("/<ICONDIR>/si", URL . "themes/icons/", $data);
$data = preg_replace("/<COPYRIGHT>/si", '<div id="footer">Powered by <a href="http://thehostingtool.com/" target="_blank">TheHostingTool</a> '. $version .'</div>', $data);
global $main;
$data = preg_replace("/<ERRORS>/si", '<span class="errors">'.$main->errors().'</span>', $data);
$data = preg_replace("/%INFO%/si", INFO, $data);
$data = preg_replace("/%ADMINDIR%/si", ADMINDIR, $data);
$data = preg_replace("/<CSRF_NAME>/si", $GLOBALS['csrf']['input-name'], $data);
