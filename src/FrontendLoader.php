<?php
namespace FrontendLoader;


/**
 * Loads frontend code like HTML, CSS, and JS
 */
class FrontendLoader
{

    public $path_prefix = null;
    public $whitelisted_script_names = null;


    public function __construct($path_prefix, $whitelisted_script_names)
    {
        if (strlen($path_prefix)) {
            $this->path_prefix = $path_prefix;
        }
        if (is_array($whitelisted_script_names &&
            count($whitelisted_script_names)) > 0) {
            $this->whitelisted_script_names = $whitelisted_script_names;
        }
    }

    /**
     * Is the user currently viewing an HTML page?
     * Things that are not HTML would be admin-ajax.php for instance
     * @return bool
     */
    public function shouldLoadFile($script_name)
    {
        $whitelisted_script_names = $this->whitelisted_script_names;
        if (!(in_array($script_name, $whitelisted_script_names) ||
                array_key_exists($script_name, $whitelisted_script_names))) {
            return false;
        }
        return true;
    }


    /**
     * Get an array of namespaces this file resides in
     * @return array
     */
    public static function getNameSpaceArray() {
        return array_reverse(explode('\\', __NAMESPACE__));
    }


    /**
     * Machinize the string
     * @param string $str
     * @param string $separator
     * @return string
     * @todo Tring to figure out whether or not this should come from a dependency
     */
    public static function machine($str, $separator = '_')
    {
        $out = strtolower($str);
        $out = preg_replace('/[^a-z0-9' . $separator . ']/', $separator, $out);
        $out = preg_replace('/[' . $separator . ']{2,}/', '', $out);
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
        $file_extension = strtolower(substr(strrchr($file_name,"."), 1));
        switch($file_extension) {
            case "gif": return "image/gif";
            case "png": return "image/png";
            case "jpeg":
            case "jpg": return "image/jpg";
            case "css": return "text/css";
            case "js":  return "application/javascript";
            default:
        }
        return false;
    }


    /**
     * Return the folder name for a given file
     * @param string $file_name
     * @return string bool
     */
    public static function getAssetFolderName($file_name)
    {
        $file_extension = strtolower(substr(strrchr($file_name,"."), 1));
        if (preg_match('/jpg|jpeg|gif|png/', $file_extension)) {
            return 'img';
        }
        if ($file_extension === 'js') {
            return 'js';
        }
        if ($file_extension === 'css') {
            return 'css';
        }
        return false;
    }


    /**
     * Determine the path for an asset using the query string
     * @return string bool
     */
    public static function getAssetPath() {
        $url_frags = parse_url($_SERVER['REQUEST_URI']);
        if (!array_key_exists('query', $url_frags)) return false;
        parse_str($url_frags['query'], $query_vars);
        if (!array_key_exists('asset', $query_vars)) return false;
        $folder_name = self::getAssetFolderName($query_vars['asset']);
        $file_name = sprintf(
            dirname(__FILE__).'/assets/%s/%s',
            $folder_name,
            $query_vars['asset']
        );
        if (file_exists($file_name)) {
           return $file_name;
        }
        return $false;
    }


    /**
     * Get the file from a the query string
     * @return string bool
     */
    public static function getAssetFileName() {
        $url_frags = parse_url($_SERVER['REQUEST_URI']);
        if (!array_key_exists('query', $url_frags)) return false;
        parse_str($url_frags['query'], $query_vars);
        if (!array_key_exists('asset', $query_vars)) return false;
        return $query_vars['asset'];
    }


    /**
     * get the prefix to be used in the path for a file
     * @return string
     */
    public function getPathPrefix() {
        $path_prefix = $this->path_prefix;
        return preg_quote($path_prefix);
    }


    /**
     * Return a file given the query string
     * @param array $query - wordpress passes this in and must be returned
     * @return file
     */
    public static function fileServe($query)
    {
     if (!array_key_exists('REQUEST_URI', $_SERVER)) return $query;
     $folder_plugin_namespace = self::machine(next(self::getNameSpaceArray()));
     $path_prefix = $this->getPathPrefix();

     if (!preg_match("/$path_prefix/assets\/(.*)$/",
         $_SERVER['REQUEST_URI'])) return $query;

     $file_path = self::getAssetPath();
     if (!$file_path) return $query;

     // check if this file is whitelisted
     if (!$this->shouldLoadFile($file_path)) return $query;

     $content_type = self::getContentType($file_path);
     header('Content-type: ' . $content_type);
     header('Content-Length: ' . filesize($file_path));
     http_response_code(200);
     readfile($file_path);
     exit;
     return $query;
    }
}
