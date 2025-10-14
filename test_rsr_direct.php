<?php
error_reporting(E_ALL);

class FFLBRO_Source_RSR {
    protected $ftp_host = 'ftp.rsrgroup.com';
    protected $ftp_user = '67271';
    protected $ftp_pass = 'h1dtuW5J';
    
    public function test_connection() {
        echo "Testing RSR FTP connection to {$this->ftp_host}...\n";
        
        $conn = ftp_connect($this->ftp_host, 21, 10);
        if (!$conn) {
            return ['success' => false, 'message' => 'FTP connection failed - server may be down'];
        }
        
        echo "FTP connection established, testing login...\n";
        $login = ftp_login($conn, $this->ftp_user, $this->ftp_pass);
        
        if ($login) {
            echo "Login successful, listing files...\n";
            ftp_pasv($conn, true);
            $files = ftp_nlist($conn, '.');
            if (is_array($files)) {
                echo "Found " . count($files) . " files/directories\n";
                echo "Sample files: " . implode(', ', array_slice($files, 0, 5)) . "\n";
            }
        }
        
        ftp_close($conn);
        
        return ['success' => $login, 'message' => $login ? 'RSR FTP connection successful' : 'FTP authentication failed'];
    }
}

$rsr = new FFLBRO_Source_RSR();
$result = $rsr->test_connection();
echo "\nFinal Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
?>
