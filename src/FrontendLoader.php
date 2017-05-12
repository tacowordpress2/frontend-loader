<?php
namespace FrontendLoader;


/**
 * Loads frontend code like HTML, CSS, and JS from inaccessible places
 */
class FrontendLoader
{

    private $new_path = null;
    private $actual_path = null;
    private $black_listed_files = null;
    private $common_path_prefix = 'assets';

    public function __construct(
        $actual_path,
        $new_path,
        $common_path_prefix = null,
        $black_listed_files = null
    ) {
        $this->setNewPath($new_path);
        $this->setActualPath($actual_path);
        $this->setCommonPathPrefix($common_path_prefix);
        $this->setBlackListedFiles($black_listed_files);
    }


    /**
     * Set the new path
     * @param string $new_path
     */
    public function setNewPath($new_path)
    {
        if (strlen($new_path)) {
            $this->new_path = $new_path;
        }
    }


    /**
     * Set the common path prefix
     * @param string $common_path_prefix
     */
    public function setCommonPathPrefix($common_path_prefix)
    {
        if (strlen($common_path_prefix)) {
            $this->common_path_prefix = $common_path_prefix;
        }
    }


    /**
     * Set the the actual path of the plugin
     * @param string $actual_path
     */
    public function setActualPath($actual_path)
    {
        if (strlen($actual_path)) {
            $this->actual_path = $actual_path;
        }
    }


    /**
     * Set which dirs are blacklisted
     * @param array $black_listed_files
     */
    public function setBlackListedFiles($black_listed_files=null)
    {
        if (!(is_array($black_listed_files) && count($black_listed_files) > 0)) {
            return;
        }
        $this->black_listed_files = $black_listed_files;
    }


    /**
     * Should the file be loaded?
     * @param string $file_name
     * @return bool
     */
    public function shouldLoadFile($file_name)
    {
        $black_listed_files = $this->black_listed_files;
        if ($black_listed_files === null) {
            return true;
        }
        if (in_array($file_name, $black_listed_files)) {
            return false;
        }
        return true;
    }


    /**
     * Machinize the string
     * @param string $str
     * @param string $separator
     * @return string
     * @todo figure out whether or not this should come from a dependency
     */
    public static function machine($str, $separator = '_')
    {
        $out = strtolower($str);
        $out = preg_replace('/[^a-z0-9' . $separator . ']/', $separator, $out);
        $out = preg_replace('/[' . $separator . ']{2,}/', $separator, $out);
        $out = preg_replace('/^' . $separator . '/', '', $out);
        $out = preg_replace('/' . $separator . '$/', '', $out);
        return $out;
    }


    /**
     * Return the content type for a file to be used with header()
     * @param string $file_name
     * @return string bool
     */
    public static function getContentType($file_name)
    {
        $file_extension = strtolower(substr(strrchr($file_name, '.'), 1));
        switch($file_extension) {
            case "gif": return "image/gif";
            case "png": return "image/png";
            case "jpeg":
            case "jpg": return "image/jpg";
            case "css": return "text/css";
            case "js":  return "application/javascript";
            case "pdf": return "application/pdf";
            default:
        }
        return false;
    }


    /**
     * Determine the path for an asset using the query string
     * @return string bool
     */
    public function getAssetPath()
    {
        $url_frags = parse_url($_SERVER['REQUEST_URI']);
        
        if (!array_key_exists('path', $url_frags)) {
            return false;
        }

        $file_name = basename($url_frags['path']);
        
        if (!preg_match('/^(.+)\.([a-z]+)$/', $file_name)) {
            return false;
        }

        // this is the path after the common path prefix
        $path_everything_after = substr(
            $url_frags['path'],
            strpos($url_frags['path'], $this->common_path_prefix)
        );

        $file_name = $this->actual_path.'/'.$path_everything_after;
        if (file_exists($file_name)) {
            return $file_name;
        }
        return false;
    }


    /**
     * Get the prefix to be used in the path for a file
     * @return string
     */
    public function getNewPath()
    {
        $new_path = $this->new_path;
        return preg_quote($new_path, '/');
    }


    /**
     * Get the file from a the query string
     * @return string bool
     */
    public static function getAssetFileName()
    {
        return preg_replace('/\?.*/', '', basename($_SERVER['REQUEST_URI']));
    }


    /**
     * Return a file given the query string
     * @param array $query - wordpress passes this in and it must be returned
     * @return file
     */
    public function fileServe($query, $callback=null)
    {
        if (!array_key_exists('REQUEST_URI', $_SERVER)) {
            return $query;
        }
        $new_path = $this->getNewPath();
        
        if (!$this->shouldLoadFile($this->getAssetFileName())) {
            return $query;
        }
        if (!preg_match("/$new_path\/(.*)$/i", $_SERVER['REQUEST_URI'])) {
            return $query;
        }

        $file_path = self::getAssetPath();

        if (!$file_path) {
            return $query;
        }

        $content_type = self::getContentType($file_path);
        header('Content-type: ' . $content_type);
        header('Content-Length: ' . filesize($file_path));
        http_response_code(200);
        readfile($file_path);
        if ($callback) {
            $callback();
        }
        exit;
        return $query;
    }
}
