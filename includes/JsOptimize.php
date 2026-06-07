<?php
/**
 * JavaScript Optimization Class
 *
 * This class handles JavaScript optimization, minification, and performance improvements
 * for the TwoSecond plugin. It extends CssOptimize to inherit CSS optimization capabilities.
 *
 * @package TwoSecond
 * @author TwoSecond Team
 */

namespace TwoSecond;

use TwoSecond\CssOptimize;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * JavaScript Optimization Class
 *
 * Handles JavaScript content optimization, minification, and performance improvements.
 * Extends CssOptimize to inherit CSS optimization capabilities.
 */
class JsOptimize extends CssOptimize {

    
	/**
	 * Modify JavaScript file cache content
	 *
	 * Applies modifications to JavaScript content before caching,
	 * including custom changes and optimizations.
	 *
	 * @param string $string The JavaScript content to modify
	 * @param string $path The file path for context
	 * @return string Modified JavaScript content
	 */
	public function twosecond_modify_file_cache_js( $string, $path ) {
		$src_array = explode('/',$path);
		$count = count($src_array);
		unset($src_array[$count-1]);
		if(!empty($this->settings['load_combined_js'])){
			$exclude_from_twosecond_changes = 0;
			$exclude_from_twosecond_changes = $this->twosecond_exclude_internal_js_changes($exclude_from_twosecond_changes, $path,$string);
			if(stripos($string,'holdready') === false && !$exclude_from_twosecond_changes){
				$string = $this->twosecond_changes_in_js($string,$path);
			}
		}
		
		$string = $this->twosecond_internal_js_customize($string, $path);
		return $string;
	}
	/**
	 * Remove lazy loading attributes from JavaScript
	 *
	 * Removes data-bglz attributes from JavaScript content
	 * to prevent background image lazy loading conflicts.
	 *
	 * @param string $string The JavaScript content to process
	 * @return string JavaScript content with lazy loading attributes removed
	 */
	public function twosecond_unlazy_load_background_image_javascript( $string ) {
		if(strpos($string,'data-bglz=1 ') !== false){
			$string = str_replace('data-bglz=1 ','',$string);
		}
		return $string;
	}
	
	/**
	 * Apply TwoSecond-specific changes to JavaScript
	 *
	 * Modifies JavaScript content to work with TwoSecond's
	 * optimization system and prevent conflicts.
	 *
	 * @param string $string The JavaScript content to modify
	 * @param string $path The file path for context (optional)
	 * @return string Modified JavaScript content
	 */
	public function twosecond_changes_in_js($string,$path=''){
		$string = str_replace('document.readyState','document.bsreadyState',$string);
		$string = preg_replace('/([\'"])load([\'"])[\s]*(!==|==|===)[\s]*([a-zA-Z_$][a-zA-Z0-9_$]*\.type)/i', '$1bs-jquery-load$2$3$4', $string);
		if(strpos($path,'jquery.fitvids.js') !== false){
			$selectors = $this->twosecond_get_tags_data($string,'var selectors = [','];','','i');
			if(!empty($selectors)){
				foreach($selectors as $selector){
					$selectorNew = str_replace("[src*","[data-src*",$selector);
					$string = str_replace($selector,trim($selectorNew).','.trim($selector),$string);
				}
			}
		}
		return $string;
	}
	/**
	 * Create cached JavaScript file
	 *
	 * Creates a cached version of a JavaScript file with optimizations applied.
	 * Handles minification, modifications, and caching for better performance.
	 *
	 * @param string $path The path to the JavaScript file to cache
	 * @return string The cached JavaScript file path
	 */
	public function twosecond_create_fileCacheJs( $path ) {
		$file_name = $this->twosecond_get_option('twosecond_rand_key', 0);
        $cache_file_path = $this->twosecond_get_cache_path('js').'/'.$file_name.'/'.ltrim($path,'/');
        if( !file_exists($cache_file_path) ){
			$path1 = explode('/',$path);
			array_pop($path1);
			$path1 = implode('/',$path1);
            $string = $this->twosecond_get_contents($this->addSettings['document_root'].$path);
            $string = $this->twosecond_modify_file_cache_js($string, $path);
			$string = !empty($string) ? $this->twosecond_compress_js($string) : $string;
            $this->twosecond_create_file($cache_file_path, $string );
        }
		if(file_exists($cache_file_path)){
			return str_replace($this->addSettings['root_cache_path'],'',$cache_file_path);
		}else{
			return $path;
		}
	    
    }
    
	/**
	 * Minify and optimize JavaScript files
	 *
	 * Main method for processing JavaScript links to combine, minify,
	 * and optimize JavaScript files for better performance.
	 * Handles exclusions, lazy loading, and CDN optimization.
	 *
	 * @param array $script_links Array of JavaScript script elements to process
	 */
	public function twosecond_optimize_scripts( $script_links ) {
		if(!empty($this->settings['exclude_page_from_load_combined_js']) && $this->twosecond_check_if_page_excluded($this->settings['exclude_page_from_load_combined_js'])){
			return ;
        }
		
		if(!empty($script_links) && !empty($this->settings['js'])){
			$force_innerjs_to_lazy_load  = !empty($this->settings['force_lazy_load_inner_javascript']) && is_array($this->settings['force_lazy_load_inner_javascript']) ? $this->settings['force_lazy_load_inner_javascript'] : array();
            $exclude_js_arr_split  = $exclude_inner_js = !empty($this->settings['exclude_both_javascript']) && is_array($this->settings['exclude_both_javascript']) ? $this->settings['exclude_both_javascript'] : array();
			foreach($exclude_js_arr_split as $key => $value){
				if(strpos($value,' sameurl') !== false){
					$exclude_js_arr[$key]['string'] = str_replace(' sameurl','',$value);
					$exclude_js_arr[$key]['sameurl'] = 1;
				}elseif(strpos($value,' defer') !== false){
					$exclude_js_arr[$key]['string'] = str_replace(' defer','',$value);
					$exclude_js_arr[$key]['defer'] = 1;
				}elseif(strpos($value,' full') !== false){
					$exclude_js_arr[$key]['string'] = str_replace(' full','',$value);
					$exclude_js_arr[$key]['full'] = 1;
				}else{	
					$exclude_js_arr[$key]['string'] = $value;
					$exclude_js_arr[$key]['defer'] = 0;
				}
			}
            $enable_cdn = $this->twosecond_check_enable_cdn('js');
			for($si=0; $si < count($script_links); $si++){
                $script = $script_links[$si];
				$script_obj = $this->twosecond_parse_link('script',str_replace($this->addSettings['js_cdn_url'],$this->addSettings['site_url'],$script_links[$si]));
				$script_text = '';
				if(!array_key_exists('src',$script_obj)){
                    $script_text = $this->twosecond_parse_script('script',$script);
                }else{
					$script_obj['src'] = trim($script_obj['src']);
				}
				
				$scriptExcludeTypeArray = ['application/javascript', 'module', 'text/javascript', 'text/jsx;harmony=true'];
                if(!empty($script_obj['type']) && !in_array(strtolower($script_obj['type']), $scriptExcludeTypeArray)){
					if(!empty($script_text) && strpos($script_text,'data-bglz') !== false){
						$script_modified = $this->twosecond_unlazy_load_background_image_javascript($script_text);
						$this->twosecond_str_replace_set_js($script_text,$script_modified);
					}
                    continue;
                }

				$script_obj = $this->twosecond_customize_script_object($script_obj, $script);
				
                if(!empty($script_obj['src'])){
				    $url_array = $this->twosecond_custom_parse_url($script_obj['src']);
					$url_array['path'] = !empty($url_array['path']) ? $url_array['path'] : '';
					if(strpos($url_array['path'],'/version') !== false){
						$url_array['path'] = preg_replace('/version(\d)*\//', '', $url_array['path']);
					}
					if(strpos($this->addSettings['document_root'],'/pub') !== false && strpos($url_array['path'],'/pub') !== false){
						$url_array['path'] = str_replace('/pub/', '/', $url_array['path']);
					}
					$url_array['path'] = '/'.ltrim($url_array['path'],'/');
                    $exclude_js = 0;
					$noPathChange = 0;
					$enable_cdn_path = 0;
                    if(!empty($exclude_js_arr) && is_array($exclude_js_arr)){
						foreach($exclude_js_arr as $ex_js){
							if(!empty($ex_js['string']) && strpos($script,$ex_js['string']) !== false){
								if(!empty($ex_js['sameurl'])){
									$noPathChange = 1;
								}elseif(!empty($ex_js['defer'])){
									$exclude_js = 2;
								}elseif(!empty($ex_js['full'])){
									$exclude_js = 3;
								}else{
									$exclude_js = 1;
								}
							}
						}
					}

					$exclude_js = $this->twosecond_external_javascript_filter($exclude_js,$script_obj,$script,$this->html);
					
					if($exclude_js){
						$this->addSettings['js_is_excluded'] = 1;
					}
					if($this->twosecond_check_enable_cdn_path($script_obj['src'], 'js')){
						$enable_cdn_path = 1;
					}
					if(strpos($url_array['path'],'./') !== false || strpos($url_array['path'],'../') !== false){
                    	$url_array['path'] = $this->twosecond_remove_dot_path_segments($url_array['path']);
                    }
					
					if(!$this->twosecond_is_external($script_obj['src'], [], 'js') && $script_obj['type'] != 'module' && $this->twosecond_endswith($url_array['path'], '.js') && $exclude_js != 1 && $exclude_js != 3){
                        $old_path = $url_array['path'];
						if(file_exists($this->addSettings['document_root'].$url_array['path']) && !$noPathChange){
							$create_file_cache_js = 1;
							if(function_exists('twosecond_create_file_cache_js')){
								$create_file_cache_js = twosecond_create_file_cache_js($url_array['path']);
							}
							if($create_file_cache_js){
								$url_array['path'] = $this->twosecond_create_fileCacheJs($url_array['path']);
								$script_obj['src'] = $this->twosecond_get_resource_url($url_array['path'], $enable_cdn && $enable_cdn_path, 0, 'js');
							}
							
						}
					}
					if($exclude_js){
                        if( $exclude_js == 3 || $exclude_js == 1){
							$script_obj['src'] = $enable_cdn && $enable_cdn_path ? str_replace($this->addSettings['site_url'],$this->addSettings['js_cdn_url'] ,$script_obj['src']) : $script_obj['src'];
							$this->addSettings['preload_resources']['all'][] = $script_obj['src'];
							$this->twosecond_str_replace_set_js($script,$this->twosecond_implode_link_array('script',$script_obj));
							continue;
						}
						if( $exclude_js == 2){
							$script_obj['defer'] = 'defer';
						}
						$script_obj['src'] = $enable_cdn && $enable_cdn_path ? str_replace($this->addSettings['site_url'],$this->addSettings['js_cdn_url'] ,$script_obj['src']) : $script_obj['src'];
						$this->twosecond_preconnect_external_url($script_obj['src']);
						$this->twosecond_str_replace_set_js($script,$this->twosecond_implode_link_array('script',$script_obj));
                        continue;
                    }
					$exclude_js_bool=0;
					if(!empty($force_innerjs_to_lazy_load)){
                        foreach($force_innerjs_to_lazy_load as $js){
                            if( !empty($js) && strpos($script,$js) !== false){
                                $exclude_js_bool=1;
                                break;
                            }
                        }
                    }
					
                    $val = $script_obj['src'];
                    if(!empty($val) && !$this->twosecond_is_external($val, [], 'js') && strpos($script, '.js') && empty($exclude_js_bool)){
						if(!empty($script_obj['type']) && $script_obj['type'] != 'text/javascript'){
							$script_obj['data-bs-type']= $script_obj['type'];
						}
						$script_obj['type'] = 'lazyJs';
						$this->twosecond_str_replace_set_js($script,$this->twosecond_implode_link_array('script',$script_obj));
					}elseif($this->twosecond_is_external($val, [], 'js') && empty($exclude_js_bool) ){
						if(!empty($script_obj['type']) && $script_obj['type'] != 'text/javascript'){
							$script_obj['data-bs-type']= $script_obj['type'];
						}
						$script_obj['type'] = 'lazyJs';
						$this->twosecond_preconnect_external_url($script_obj['src']);
						$this->twosecond_str_replace_set_js($script,$this->twosecond_implode_link_array('script',$script_obj));
					}elseif($exclude_js_bool){
						if(!empty($script_obj['type']) && $script_obj['type'] != 'text/javascript'){
							$script_obj['data-bs-type']= $script_obj['type'];
						}
						$script_obj['src'] = $enable_cdn && $enable_cdn_path ? str_replace($this->addSettings['site_url'],$this->addSettings['js_cdn_url'] ,$script_obj['src']) : $script_obj['src'];
						$script_obj['type'] = 'lazyExJs';
						$script_obj = $this->twosecond_external_javascript_customize($script_obj, $script);
						$this->twosecond_preconnect_external_url($script_obj['src']);
						$this->twosecond_str_replace_set_js($script,$this->twosecond_implode_link_array('script',$script_obj));
                    }
                }else{
                    
                    $inner_js = $script_text;
                    $exclude_js_bool = 0;
					$force_js_bool = 0;
                    $exclude_js_bool = $this->twosecond_check_js_if_excluded($inner_js, $exclude_inner_js);
					if(!empty($force_innerjs_to_lazy_load)){
                        foreach($force_innerjs_to_lazy_load as $js){
                            if(strpos($script_text,$js) !== false){
                                $exclude_js_bool=0;
								$force_js_bool = 1;
                                break;
                            }
                        }
                    }
					$script_modified = $this->twosecond_get_modified_script_tag($script,$script_obj,$script_text,$si,$exclude_js_bool,$force_js_bool);
					$this->twosecond_str_replace_set_js($script,$script_modified);
                }
            }
		}
        
        
    }

	/**
	 * Get modified script tag for optimization
	 *
	 * Creates a modified script tag based on optimization settings,
	 * handling exclusions, lazy loading, and custom modifications.
	 *
	 * @param string $script The original script HTML
	 * @param array $script_obj Script object containing attributes
	 * @param string $script_text The script content text
	 * @param int $si Script index for identification
	 * @param bool $exclude_js_bool Whether JavaScript is excluded
	 * @param bool $force_js_bool Whether JavaScript is forced to load
	 * @return string Modified script tag HTML
	 */
	public function twosecond_get_modified_script_tag( $script, $script_obj, $script_text, $si, $exclude_js_bool, $force_js_bool ) {
		$script_text = $this->twosecond_inner_js_customize($script_text);

		if($tag = $this->twosecond_load_script_tag_in_url($script_text,$si)){
			$this->twosecond_get_url_from_inline_script($script);
			return $tag;
		}
		if(!empty($script_obj['type']) && $script_obj['type'] != 'text/javascript'){
			$script_obj['data-bs-type']= $script_obj['type'];
		}
		if(!empty($exclude_js_bool) && $exclude_js_bool != 2){
			$script_modified = '<script ';
		}elseif($exclude_js_bool == 2){
			$script_modified = '<script type="lazyJs" ';
		}elseif($force_js_bool){
			$script_modified = '<script type="lazyExJs" ';
		}else{
			$script_modified = '<script type="lazyJs" ';
		}
		if(!empty($script_obj) && is_array($script_obj) && count($script_obj) > 0){
			foreach($script_obj as $key => $value){
				if($key != 'type' && $key != 'html'){
					$script_modified .= $key.'="'.$value.'" ';
				}
			}
		}
		$script_text = $this->twosecond_unlazy_load_background_image_javascript($script_text);
		if(!empty($exclude_js_bool) && $exclude_js_bool != 2){
			$script_modified = $script_modified.'>'.$script_text.'</script>';
		}else{
			if(!empty($this->settings['load_combined_js']) && $this->settings['load_combined_js'] == 'after_page_load'){
				$script_text = $this->twosecond_changes_in_js($script_text);
			}
			$script_modified = $script_modified.'>'.$script_text.'</script>';
		}
		return $script_modified;
	}
	/**
	 * Extract URLs from inline script content
	 *
	 * Parses inline script content to extract URLs and
	 * add preconnect hints for external resources.
	 *
	 * @param string $script The script content to parse
	 */
	public function twosecond_get_url_from_inline_script( $script ) {
		if ($script){
			$script = str_replace(["\r", "\n", "\t"," "], '', $script);
			preg_match_all('/src\=[\'|\"](h?t?t?p?s?:?\/\/[^\s"\']+)/i', $script, $matches);
			if(!empty($matches[1])){
				foreach ($matches[1] as $url) {
					$parsedUrl = $this->twosecond_parse_url($url);
					if (!isset($parsedUrl['host']) || strpos($parsedUrl['host'], $this->addSettings['site_url_array']['host']) === false) {
						$this->twosecond_preconnect_external_url((!empty($parsedUrl['scheme']) ? $parsedUrl['scheme'] : 'https').'://'.$parsedUrl['host']);
					}
				}
			}
		}
	}

	/**
	 * Add preconnect for external URLs
	 *
	 * Adds preconnect hints for external JavaScript resources
	 * to improve loading performance and enable INP optimization.
	 *
	 * @param string $src The external URL to preconnect
	 */
	public function twosecond_preconnect_external_url( $src ) {
		if(empty($this->addSettings['preload_resources']['preconnect'])){
			$this->addSettings['preload_resources']['preconnect'] = [];
		}
		if(!empty($this->settings['enable_inp']) && $this->twosecond_is_external($src, [], 'js')){
			$srcArr = $this->twosecond_parse_url($src);
			$domain = (!empty($srcArr['scheme']) ? $srcArr['scheme'] : 'https').'://'.(!empty($srcArr['host']) ? $srcArr['host'] : '');
			if(!in_array($domain,$this->addSettings['preload_resources']['preconnect'])){
				$this->addSettings['preload_resources']['preconnect'][] = $domain;
			}
		}
	}
	/**
	 * Check if JavaScript should be excluded from optimization
	 *
	 * Determines whether inline JavaScript should be excluded from
	 * optimization based on content analysis and exclusion settings.
	 *
	 * @param string $inner_js The inline JavaScript content
	 * @param array $exclude_inner_js Array of JavaScript exclusions
	 * @return int Exclusion status (0 = include, 1 = exclude, 2 = defer)
	 */
	public function twosecond_check_js_if_excluded( $inner_js, $exclude_inner_js ) {
		$exclude_js_bool=0;
		if(strpos($inner_js,'moment.') === false && strpos($inner_js,'wp.') === false && strpos($inner_js,'.noConflict') === false && strpos($inner_js,'wp.i18n') === false){
			$exclude_js_bool=2;
		}
		if(strpos($inner_js,'DOMContentLoaded') !== false || strpos($inner_js,'jQuery(') !== false || strpos($inner_js,'$(') !== false || strpos($inner_js,'jQuery.') !== false || strpos($inner_js,'$.') !== false){
			$exclude_js_bool=2;
		}
		
		if(!empty($exclude_inner_js)){
			foreach($exclude_inner_js as $js){
				if(!empty($js) && strpos($inner_js,$js) !== false){
					return 1;
					break;
				}
			}
		}
		$exclude_js_bool = $this->twosecond_inner_js_excluded($exclude_js_bool, $inner_js);
		return $exclude_js_bool;
	}
	
	/**
	 * Generate lazy loading JavaScript code
	 *
	 * Creates the JavaScript code responsible for lazy loading
	 * JavaScript files and handling optimization settings.
	 *
	 * @return string JavaScript code for lazy loading functionality
	 */
	public function twosecond_lazy_load_javascript() {
		$lazyload_by_px = function_exists('twosecond_lazyload_by_px') ? twosecond_lazyload_by_px() : 200;
        $script = 'var twosecondelem = window.innerWidth<768?\'touchstart\':\'click\';var twosecondLazyloadByPx='. $lazyload_by_px .', twosecondLazyloadJs = '.(((!empty($this->settings['load_combined_js']) && $this->settings['load_combined_js'] == 'after_page_load') && !empty($this->settings['js'])) || (!empty($this->settings['exclude_page_from_load_combined_js']) && $this->twosecond_check_if_page_excluded($this->settings['exclude_page_from_load_combined_js'])) || empty($this->settings['js']) ? 1 : 0).',twosecondExcludedJs = '.(!empty($this->addSettings['js_is_excluded']) ? 1 : 0).', twosecondInp = '.(!empty($this->settings['enable_inp']) ? 1 : 0).',';
		$script.='twosecondBlankImgUrl="'.$this->twosecond_get_cache_url('blank.png').'",';
		$script.='twosecondCallUrl="'. $this->twosecond_get_ajax_url() .'?action=twosecond_ajax_call&token='.$this->pageToken.'",';
		$cssFilesCount = $this->twosecond_css_files_count($this->addSettings['full_url_without_param']);
		if((is_array($this->webpEnqueImageUrls) && count($this->webpEnqueImageUrls) > 0 && file_exists($this->addSettings['webp_path'].'/'.$this->pageToken)) || file_exists($this->addSettings['critical_css_path'].'/tokens/'.$this->pageToken) || (!$cssFilesCount['mobile'] && $this->twosecond_is_mobile_device()) || (!$cssFilesCount['desktop'] && !$this->twosecond_is_mobile_device())){
			$script.='twosecondCall=1;';
		}else{
			$script.='twosecondCall=0;';
		} 
		return $script.$this->twosecond_get_contents(TWOSECOND_DIR.'assets/js/script-load.min.js');
	}
	/**
	 * Generate lazy loading images JavaScript code
	 *
	 * Creates the JavaScript code responsible for lazy loading
	 * images and handling image optimization settings.
	 *
	 * @return string JavaScript code for image lazy loading functionality
	 */
	public function twosecond_lazy_load_images() {
		return $this->twosecond_get_contents(TWOSECOND_DIR.'assets/js/img-lazyload.js');
    }
	/**
	 * Load script tag content from URL
	 *
	 * Processes script tags to load their content from URLs
	 * and create cached versions for optimization.
	 *
	 * @param string $script_tag The script tag content to process
	 * @param int $i The script index for identification
	 * @return string|false Modified script tag HTML or false if not applicable
	 */
	public function twosecond_load_script_tag_in_url( $script_tag, $i ) {
		$load_script_tag_in_url= !empty($this->settings['load_script_tag_in_url']) && is_array($this->settings['load_script_tag_in_url']) ? $this->settings['load_script_tag_in_url'] : array();
		$scriptContentFile = array();
		$file_name = $this->twosecond_get_option('twosecond_rand_key',0).$i;
		foreach($load_script_tag_in_url as $ex_script){
			if(!empty($ex_script) && !empty($script_tag) && strpos($script_tag, $ex_script) !== false){
				$file_name .= $ex_script;
				$scriptContentFile = $script_tag;
				break;
			}
		}
		if(!empty($scriptContentFile)){
			$file_name_cache = md5($file_name).'.js';
			if(!file_exists($this->twosecond_get_cache_path('js').'/'.$file_name_cache)){
				$this->twosecond_create_file($this->twosecond_get_cache_path('js').'/'.$file_name_cache,$scriptContentFile);
			}
			$defer = 'type="lazyJs" src=';
			return '<script '.$defer.'"'.$this->twosecond_get_cache_url('js').'/'.$file_name_cache.'"></script>';
		}
		return false;
	}
}