<?php
if (!defined('ABSPATH')) exit;

class FFLBRO_Source_RSR {
    private $ftp_host = 'ftp.rsrgroup.com';
    private $ftp_port = 21;
    private $ftp_user = '67271';
    private $ftp_pass = 'h1dtuW5J';
    
    public function test_connection() {
        try {
            $connection = ftp_ssl_connect($this->ftp_host, $this->ftp_port, 30);
            if (!$connection) {
                return array('success' => false, 'message' => 'Failed to connect to RSR FTP server');
            }
            
            if (!ftp_login($connection, $this->ftp_user, $this->ftp_pass)) {
                ftp_close($connection);
                return array('success' => false, 'message' => 'FTP login failed');
            }
            
            ftp_pasv($connection, true);
            $files = ftp_nlist($connection, '.');
            ftp_close($connection);
            
            return array(
                'success' => true, 
                'message' => 'RSR FTP connection successful!',
                'files_found' => count($files)
            );
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'Connection error: ' . $e->getMessage());
        }
    }
}
