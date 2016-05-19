<?php
define('AWSSDK_PATH', ROOT_PATH . 'Lib/awssdk/');
require AWSSDK_PATH . 'aws-autoloader.php';
use Aws\S3\S3Client;
use Aws\CloudFront\CloudFrontClient;


class CDN{

    private $_s3 = null;
    private $_cf = null;
    private $_bucket = 'ins-files';

    public function __construct()
    {
        $credentials = new Aws\Credentials\Credentials('AKIAJ6XKJW5D7JNRDMZA', 'DVKG1YDgdWT/LwOQkzMDj1sCryW/1Ex3JWlZkK4B');
        $this->_cf = CloudFrontClient::factory(array(
            'version' => 'latest',
            'credentials' => $credentials,
            'region' => 'us-east-1',
            //'ssl.certificate_authority' => 'D:\wamp\bin\php\php5.5.12\cacert.pem',
            'scheme' => 'http',
        ));
        $this->_s3 = S3Client::factory(array(
            'version' => 'latest',
            'credentials' => $credentials,
            'region' => 'us-west-2',
           // 'ssl.certificate_authority' => 'D:\wamp\bin\php\php5.5.12\cacert.pem',
            'scheme' => 'http',
        ));
    }

    public function put_cdn_file($file){
        $cdn_dir = C('PUT_CDN_DIR');
        $key = "zips/{$cdn_dir}/" . basename($file);
        $status = $this->put_file($file, $key, $this->_bucket);

        $did_arr = array('E3LGYSST79LKBZ', 'E23UAJGEZSW65H', 'ECY8I9R9M4QO5');
        foreach($did_arr as $did){
            $this->sync2cf(array("/{$cdn_dir}/" . basename($file)), $did);
        }
    }

    private function put_file($filepath, $filename, $bucket)
    {
        if(!file_exists($filepath)){
            return false;
        }

        $md5_str = md5_file($filepath);
        $params = array(
            'Bucket' => $bucket,
            'Key' => $filename,
            'SourceFile' => $filepath,
            'ContentType' => 'application/octet-stream',
            'ACL' => 'public-read',
            'StorageClass' => 'REDUCED_REDUNDANCY',
            'Metadata' => array(
                'Content-MD5' => $md5_str,
            ),
        );
        $result = $this->_s3->putObject($params);
        return $result;
    }

    //同步到cloudfront-cdn
    private function sync2cf($keys, $did)
    {
        $params = array('DistributionId' => $did,
            'InvalidationBatch' => [
                'CallerReference' => md5(microtime()),
                'Paths' => [
                    'Items' =>$keys,
                    'Quantity' =>count($keys),
                ],
            ]
        );

        $result = $this->_cf->createInvalidation($params);
        return $result;
    }

}