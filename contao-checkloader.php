<?php

/**
 * contao-checkloader extension for Contao Open Source CMS
 *
 * Copyright (C) 2014, e-spin Berlin 
 *
 * @package contao-checkloader
 * @author  e-spin Berlin <http://www.e-spin.de>
 * @author  Ingolf Steinhardt <info@e-spin.de> 
 * @author  Codefog <http://codefog.pl>
 * @author  Kamil Kuzminski <kamil.kuzminski@codefog.pl>
 * @license Commercial
 */

define('TL_ROOT', dirname(__FILE__));

/**
 * Class ContaoCheckDownloader
 *
 * Provides a methods to download Contao-check
 */
class ContaoCheckDownloader
{

    /**
     * Source URL
     * @var string
     */
    protected $strUrl = 'https://github.com/contao/check/archive/master.zip';
    
    /**
     * Zip-File
     * @var string
     */
    protected $strZipFile = 'contao-check.zip';    


    /**
     * Download the file
     */
    public function run()
    {
        // Check if the cURL is loaded
        if (!extension_loaded('curl'))
        {
            die('You must enable the cURL extension!');
        }

        // Check if the ZIP is loaded
        if (!extension_loaded('zip'))
        {
            die('You must enable the ZIP extension');
        }

        // Delete the existing folder
        if (file_exists(TL_ROOT . '/check'))
        {
            $this->deleteFolder('check');
        }       

        // Delete the old file
        if (is_file(TL_ROOT . '/' . $this->strZipFile))
        {
            $this->deleteFile($this->strZipFile);
        }

        // Try to download the ZIP file
        if (@file_put_contents(TL_ROOT . '/' . $this->strZipFile, $this->downloadCurl()) === false)
        {
            die('Could not download the ZIP file!');
        }

        $objZip = new ZipArchive();

        // The ZIP could not be open
        if ($objZip->open(TL_ROOT . '/' . $this->strZipFile) !== true)
        {
            die('Could not extract the ZIP file!');
        }

        $objZip->extractTo(TL_ROOT . '/');
        $objZip->close();

        // Delete the ZIP file
        $this->deleteFile($this->strZipFile);

        // Move the "check" folder to root
        rename(TL_ROOT . '/check-master/check', TL_ROOT . '/check');

        // Remove the unzipped folder
        $this->deleteFolder('check-master');

        // Redirect to the check tool
        header('HTTP/1.1 302 Found');
        header('Location: ' . (($_SERVER['SSL_SESSION_ID'] || $_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . str_replace(basename(__FILE__), '', $_SERVER['SCRIPT_NAME']) . 'check');
    }


    /**
     * Download using cURL
     * @return string
     */
    protected function downloadCurl()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $this->strUrl);
        $return = curl_exec($ch);
        curl_close($ch);

        return $return;
    }


    /**
     * Recursively delete the folder
     * @param string
     */
    protected function deleteFolder($strFolder)
    {
        foreach (scandir(TL_ROOT . '/' . $strFolder) as $strFile)
        {
            if ($strFile == '.' || $strFile == '..')
            {
                continue;
            }

            // Delete the folder
            if (is_dir(TL_ROOT . '/' . $strFolder . '/' . $strFile))
            {
                $this->deleteFolder($strFolder . '/' . $strFile);
            }
            // Delete the file
            else
            {
                $this->deleteFile($strFolder . '/' . $strFile);
            }
        }

        // Delete the folder
        @rmdir(TL_ROOT . '/' . $strFolder);
    }


    /**
     * Delete the file
     * @param string
     */
    protected function deleteFile($strFile)
    {
        @unlink(TL_ROOT . '/' . $strFile);
    }
}

$objDownloader = new ContaoCheckDownloader();
$objDownloader->run();
