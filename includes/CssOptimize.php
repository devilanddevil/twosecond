<?php
/**
 * CSS Optimization Class
 *
 * This class handles CSS optimization, minification, and performance improvements
 * for the TwoSecond plugin. It extends TwoSecond to inherit core functionality.
 *
 * @package TwoSecond
 * @author TwoSecond Team
 */


namespace TwoSecond;

use TwoSecond\TwoSecond;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSS Optimization Class
 *
 * Handles CSS content optimization, minification, and performance improvements.
 * Extends TwoSecond to inherit core functionality.
 */
class CssOptimize extends TwoSecond {
    
    	/**
	 * Remove CSS comments from minified content
	 *
	 * Strips all CSS comments from the CSS content to reduce file size.
	 *
	 * @param string $minify The CSS content to process
	 * @return string CSS content with comments removed
	 */
	public function twosecond_remove_css_comments( $minify ) {
		$minify = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $minify );
		return $minify;
	}

	/**
	 * Compress CSS content
	 *
	 * Removes unnecessary whitespace, newlines, and tabs from CSS to reduce file size.
	 *
	 * @param string $minify The CSS content to compress
	 * @return string Compressed CSS content
	 */
	public function twosecond_css_compress( $minify ) {
    	$minify = str_replace( array("\r\n", "\r", "\n", "\t",'  ','    ', '    '), ' ', $minify );
		$minify = str_replace( array(": ", ":: "), array(':','::'), $minify );
		if(empty($minify)){
			$minify = '/* no-css-in-file */';
		}
    	return $minify;
    }

	/**
	 * Convert relative paths to absolute paths in CSS
	 *
	 * Processes CSS content to convert relative URLs to absolute URLs,
	 * ensuring proper resource loading regardless of the CSS file location.
	 *
	 * @param string $url The base URL for resolving relative paths
	 * @param string $string The CSS content containing URLs
	 * @return string CSS content with absolute URLs
	 */
	public function twosecond_relative_to_absolute_path( $url, $string ) {
		$url_arr = $this->twosecond_custom_parse_url($url);
        $url = $this->addSettings['site_url'].$url_arr['path'];
        
        if(strpos($string,'@import "') !== false || strpos($string,"@import '") !== false){
           $string = preg_replace('/(@import\s*)[\"|\'](.*)(\.css)[\"|\']/', '$1url("$2$3")', $string);
        }
        
        $matches = $this->twosecond_get_tags_data($string,'url(',')');
		return $this->twosecond_convert_arr_relative_to_absolute($string, $url, $matches);
    
    }

	/**
	 * BS convert array relative to absolute
	 *
	 * @param string $string String.
	 * @param string $url Url.
	 * @param array $matches Matches.
	 * @return string
	 */
	function twosecond_convert_arr_relative_to_absolute($string, $url, $matches){
		$webp_enable = $this->addSettings['webp_enable'];
		$url_parent_url = $this->twosecond_get_path_from_url($url);
		if($this->addSettings['is_multisite_sub_domain']){
			$url_parent_url = str_replace($this->addSettings['site_url'],$this->addSettings['network_site_url'], $url_parent_url);
		}

		foreach($matches as $match){
			if(strpos($match,'{{') !== false || strpos($match,'data:') !== false || strpos($match,'chrome-extension:') !== false){
                continue;
    		}
		    $org_match = $match;
			$quote = $this->twosecond_get_url_quote_type($match);
            $match1 = $this->twosecond_replace_url_brackets($match);
            $match1 = trim($match1);
	        if(strpos($match1,'//') > 7){
                $match1 = substr($match1, 0, 7).str_replace('//','/', substr($match1, 7));
            }
            if(empty($match1) || strpos(substr($match1, 0, 1),'#') !== false){
				continue;
			}
			if($this->addSettings['is_multisite_sub_domain'] && strpos($match1,$this->addSettings['site_url']) != false){
				$match1 = str_replace($this->addSettings['site_url'],$this->addSettings['network_site_url'],$match1);
			}
			if(strpos($match,'cdnjs.cloudflare.com') !== false){
				$img_arr = explode('?',$match1 );
				$ext = pathinfo($img_arr[0], PATHINFO_EXTENSION);
                if(strpos($url,'index.php') === false && $ext == 'css'){
					$response = $this->twosecond_remote_get($match1);
					if(!empty($response)){
						$string = str_replace('@import '.$match.';',$response, $string);
					}
					continue;
				}
            }
			if(strpos($match,'fonts.gstatic.com') !== false){
				$match1 = esc_url($match1);
				$cssUrl = $this->twosecond_localize_google_font($match1);
				if(!empty($cssUrl)){
					$string = str_replace($match1,$cssUrl, $string);
				}
			}
			if(strpos($match,'fonts.googleapis.com') !== false){
				$match1 = esc_url($match1);
                if(strpos($url,'index.php') !== false){
					if(!empty($this->settings['localize_google_fonts'])) {
						$cssUrl = $this->twosecond_localize_google_fonts([$match1]);
						if(!empty($cssUrl[0])){
							$string = str_replace('@import '.$match.';','@import url('.$cssUrl[0].');', $string);
						}
					}
				}else{
					$match1 = html_entity_decode($match1);
					$match_arr = $this->twosecond_custom_parse_url($match1);
					$match_arr['scheme'] = empty($match_arr['scheme']) ? 'https' : $match_arr['scheme'];
					$match_arr['query'] = empty($match_arr['query']) ? '' : '?'.$match_arr['query'];
					$match_arr['host'] = empty($match_arr['host']) ? $this->addSettings['site_url_array']['host'] : $match_arr['host'];
					$response = $this->twosecond_remote_get($match_arr['scheme'].'://'.$match_arr['host'].$match_arr['path'].$match_arr['query']);
					if(!empty($response)){
						$string = str_replace('@import '.$match.';',$response, $string);
					}
				}
                continue;
			}
			$match1 = str_replace($this->addSettings['css_cdn_url'],$this->addSettings['site_url'],$match1);
			if($this->twosecond_is_external($match1, [], 'css')){
                continue;
			}
			$match_arr = $this->twosecond_custom_parse_url($match1);
			if(substr($match1, 0, 1) == '/' || strpos($match1,'http') !== false){
				if($this->addSettings['is_multisite_sub_domain']){
					$match1 = file_exists($this->addSettings['document_root'].'/'.trim($match_arr['path'],'/')) ? $this->addSettings['network_site_url'].'/'.trim($match_arr['path'],'/') : $match1;
				}else{
					$match1 = file_exists($this->addSettings['document_root'].'/'.trim($match_arr['path'],'/')) ? $this->addSettings['domain_url'].'/'.trim($match_arr['path'],'/') : $match1;
				}
				$import_match = $match1;
			}else{
				$match1 = $url_parent_url.'/'.trim($match_arr['path'],'/');
				$import_match = $url_parent_url.'/'.trim($match_arr['path'],'/');
				$match_arr = $this->twosecond_custom_parse_url($match1);
			}
			if(strpos($match1,'..') !== false){
				$match1 = $this->twosecond_remove_dot_path_segments($match1);
			}
			if(strpos($match1,'.css')!== false && strpos($string,'@import '.$match)!== false && $url != $this->addSettings['full_url_without_param'].'/index.php'){
                $string = str_replace('@import '.$match.';',$this->twosecond_relative_to_absolute_path($this->twosecond_remove_dot_path_segments($import_match),$this->twosecond_get_contents($this->twosecond_remove_dot_path_segments(str_replace($this->addSettings['site_url'],$this->addSettings['document_root'],$import_match)))), $string);
                continue;
			}
			$img_arr = explode('?',$match1 );
			$ext = '.'.pathinfo($img_arr[0], PATHINFO_EXTENSION);
			if($ext == '.'){
                continue;
			}
			if(in_array($ext, ['.jpg', '.webp', '.jpeg', '.png']) && !$this->twosecond_exclude_image_from_convert_to_webp($match1)){
				list($img_root_path, $img_root_url) = $this->twosecond_get_img_root_path();
				$webp_enable = $this->addSettings['webp_enable'];
				$imgsrc_filepath = $this->twosecond_get_resource_root_path($match1, $img_root_url, $img_root_path);

				$imgsrc_webpfilepath = $this->twosecond_get_img_webp_path($img_root_path, $imgsrc_filepath);
				$imgsrc_webpfilepath595xh = $this->twosecond_get_img_webp_path595xh($imgsrc_webpfilepath,$imgsrc_filepath);
				if($this->addSettings['is_mobile'] && !empty($this->settings['resp_bg_img'])){
					if(file_exists($imgsrc_webpfilepath595xh)){
						$imgsrc_webpfilepath = $imgsrc_webpfilepath595xh;
					}else{
						$this->addSettings['twosecond_responsive_img_urls'][] = $img_arr[0];
					}
				}
				if((in_array($ext, $webp_enable) || $ext === '.webp') && !empty($imgsrc_webpfilepath)){
					if(file_exists($imgsrc_webpfilepath) && (!empty($this->addSettings['disable_htaccess_webp']) || !file_exists($this->addSettings['document_root']."/.htaccess") || $this->addSettings['image_cdn_url'] != $this->addSettings['site_url'] )){
						$match1 = $this->twosecond_get_media_resource_url($imgsrc_webpfilepath);
					}else{
						$this->webpEnqueImageUrls[] = $img_arr[0];
					}
				}
			}
			if(substr($match1, 0, 1) == '/' || substr($match1, 0, 4) == 'http'){
				if($this->addSettings['image_cdn_url'] == $this->addSettings['site_url']){
					if($this->addSettings['is_multisite_sub_domain']){
						$match1 = str_replace($this->addSettings['network_site_url'],$this->addSettings['site_url'],$match1);
					}
					$replacement = 'url('.$quote.$match1.$quote.')';
				}
				else{
					$replacement = 'url('.$quote.$this->addSettings['domain_url'].'/'.trim($match_arr['path'],'/').$quote.')';
				}
			}else{
				if($this->addSettings['is_multisite_sub_domain']){
					$match1 = str_replace($this->addSettings['network_site_url'],$this->addSettings['site_url'],$match1);
				}
				$replacement = 'url('.$quote.$this->addSettings['domain_url'].'/'.trim($match_arr['path'],'/').$quote.')';
			}
			if (in_array(strtolower($ext), array('.otf', '.ttf', '.woff', '.woff2', '.gtf', '.mmm', '.pea', '.tpf', '.ttc', '.wtf', '.eot', '.pfb', '.pfm', '.fon', '.fnt')) && $this->twosecond_check_enable_cdn('font') && !$this->twosecond_check_excluded_path($replacement, $this->addSettings['font_exclude_cdn_path'])) {
                $replacement  = str_replace($this->addSettings['site_url_array']['host'], $this->addSettings['font_url_array']['host'],$replacement );
            }
			if(in_array($ext, array('.jpeg', '.jpg', '.png', '.gif', '.webp', '.svg', '.tiff', '.psd', '.raw', '.bmp', '.heif', '.indd')) && $this->twosecond_check_enable_cdn('image') && !$this->twosecond_check_excluded_path($replacement, $this->addSettings['image_exclude_cdn_path'])){
				$replacement  = str_replace($this->addSettings['site_url_array']['host'], $this->addSettings['image_url_array']['host'],$replacement );
			}
			if(strpos($url,'index.php') !== false){
				$this->twosecond_str_replace_set_img($org_match, $replacement);
				$this->twosecond_convert_html_images_to_webp();
			}else{
            	$string = str_replace($org_match, $replacement, $string);
			}
        }
		if(strpos($url,'index.php') === false){
			$string = str_replace($this->addSettings['domain_url'], '', $string);
		}
		if (!empty($this->settings['inlineToUrlSVG'])) {
			$string = str_replace([' svg,', ' svg ', ' svg{'], [' .twosecond-svg,', ' .twosecond-svg ', ' .twosecond-svg{'], $string);
		}
		return $string;
	}
	
	/**
	 * Create cached CSS file
	 *
	 * Creates a cached version of a CSS file with optimizations applied.
	 * Handles minification, comment removal, and relative path conversion.
	 *
	 * @param string $path The path to the CSS file to cache
	 * @return string The cached CSS file path
	 */
	public function twosecond_create_file_cache_css( $path, $root_path = '' ) {
		$file_name = $this->twosecond_get_option('twosecond_rand_key', 0);
		$new_path = $this->addSettings['css_ext'] != '.css' ? $this->twosecond_right_replace($path,'.css',$this->addSettings['css_ext']) : $path ;
        $cache_file_path = $this->twosecond_get_cache_path('css').'/'.$file_name.'/'.ltrim($new_path,'/');
		$root_path = !empty($root_path) ? $root_path : $this->addSettings['document_root'];
        if( !file_exists($cache_file_path) ){
			$css = $this->twosecond_get_contents($root_path.$path);
			$css = $this->twosecond_internal_css_customize($css, $path);

			$css = $this->twosecond_remove_css_comments($css);
			$minify = $this->twosecond_relative_to_absolute_path($this->addSettings['site_url'].$path,$css);
			$css_minify = 1;
			
			$css_minify = $this->twosecond_internal_css_minify($css_minify, $css, $path);
			if($css_minify){
				$minify = $this->twosecond_css_compress($minify);
			}
			$this->twosecond_create_file($cache_file_path, $minify);
		}
        if(!file_exists($cache_file_path)){
			return $path;
		}else{
			return str_replace($this->addSettings['root_cache_path'],'',$cache_file_path);
		}
    }
    
	/**
	 * Minify and combine CSS files
	 *
	 * Processes CSS links to combine, minify, and optimize CSS files for better performance.
	 * Handles exclusions, force lazy loading, and CDN optimization.
	 *
	 * @param array $css_links Array of CSS link elements to process
	 */
	public function twosecond_minify_css( $css_links ) { 
		if ( ! empty( $this->settings['exclude_page_from_load_combined_css'] ) && $this->twosecond_check_if_page_excluded( $this->settings['exclude_page_from_load_combined_css'] ) ) {
			return $this->html;
		}
		$combined_css_files = [];
		if(!empty($css_links) && !empty($this->settings['css'])){
			$excludeCssFromMinify = !empty($this->settings['exclude_css']) && is_array($this->settings['exclude_css']) ? $this->settings['exclude_css'] : array();
			$excludeCssFromMinifyArr = array();
			foreach($excludeCssFromMinify as $key => $value){
				if(strpos($value,' 1') !== false){
					$excludeCssFromMinifyArr[$key]['string'] = str_replace(' 1','',$value);
					$excludeCssFromMinifyArr[$key]['cache'] = 1;
				}else{	
					$excludeCssFromMinifyArr[$key]['string'] = $value;
					$excludeCssFromMinifyArr[$key]['cache'] = 0;
				}
			}
			$force_lazyload_css	= !empty($this->settings['force_lazyload_css']) && is_array($this->settings['force_lazyload_css']) ? $this->settings['force_lazyload_css'] : array();

			$force_lazyload_css = $this->twosecond_customize_force_lazy_css($force_lazyload_css);

			$enable_cdn = $this->twosecond_check_enable_cdn('css');
			
			$css_links_arr = array();
			foreach($css_links as $key => $css){
				$cssNew = $css;
				// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Modifying existing HTML link tags in output buffer, not enqueueing new stylesheets
				if(preg_match('/rel=["\']preload["\'].*?as=["\']style["\'].*?onload=/i', $cssNew)){
					// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Modifying existing preload link to stylesheet in output buffer
					$cssNew = preg_replace('/\s+rel=["\']preload["\']/i', ' rel="stylesheet"', $cssNew);
					$cssNew = preg_replace('/\s+as=["\']style["\']/i', '', $cssNew);
					$cssNew = preg_replace('/\s+onload=["\'][^"\']*["\']/i', '', $cssNew);
				}
				$css_obj = $this->twosecond_parse_link('link',str_replace($this->addSettings['css_cdn_url'],$this->addSettings['site_url'],$cssNew));
				// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Parsing existing HTML link tags, not enqueueing stylesheets
				if( !empty($css_obj['rel']) && strpos($css_obj['rel'],'stylesheet') !== false && !empty($css_obj['href']) ){
					$css_obj['rel'] = 'stylesheet';
					$css_links_arr[] = array('arr'=>$css_obj,'css'=>$css);
				}elseif(empty($css_obj['rel'])){
					$css_links_arr[] = array('arr'=>array(),'css'=>$css);
				}
			}

			foreach($css_links_arr as $key => $link_arr){
				$css = $link_arr['css'];
				$css_obj = $link_arr['arr'];
				$enable_cdn_path = 0;
				if(!empty($css_obj['rel']) && $css_obj['rel'] == 'stylesheet' && !empty($css_obj['href'])){
					if(!empty($css_obj['media']) && strtolower($css_obj['media']) == 'print'){
						$this->html = str_replace($css, str_replace('href', 'data-href', $css), $this->html);
						continue;
					}
					
					if($this->twosecond_check_enable_cdn_path($css_obj['href'], 'css')){
						$enable_cdn_path = 1;
					}
					$url_array = $this->twosecond_parse_css_url($css_obj['href']);
					$url_array = $this->twosecond_modify_css_url_array($url_array);
					if($this->twosecond_check_css_excluded($excludeCssFromMinifyArr, $css, $css_obj, $css_links_arr, $enable_cdn, $enable_cdn_path)){
						continue;
					}
					if($this->twosecond_check_force_lazy_load($force_lazyload_css,$css,$url_array,$css_obj,$link_arr, $enable_cdn, $enable_cdn_path)){
						continue;
					}
					
					list($css_obj,$url_array,$return) = $this->twosecond_get_optimized_css_url($css_obj,$url_array,$css, $enable_cdn, $enable_cdn_path);
					if($return){
						continue;
					}
					$combined_css_files = $this->twosecond_get_css_cache_url($combined_css_files,$key,$css_obj,$url_array,$css, $enable_cdn, $enable_cdn_path);
				}
			}
			if(!empty($remove_css_tags)){
				foreach($remove_css_tags as $css){
					$this->twosecond_str_replace_set_css($css,'');
				}
			}
			if(!$this->twosecond_check_ignore_critical_css()){
				if(is_array($combined_css_files)){
					$this->addSettings['critical_css'] = $this->twosecond_get_critical_css_filename($combined_css_files);
				}
			}
			$this->twosecond_render_css($combined_css_files,$css_links_arr,0);
		}
		
	}
	/**
	 * Generate CSS to exclude background images from lazy loading
	 *
	 * Creates CSS rules to prevent specific elements from having their
	 * background images lazy loaded based on exclusion settings.
	 *
	 * @return string CSS rules for excluding background images from lazy loading
	 */
	public function twosecond_exclude_lazy_load_background_images() {
		$content = [];
		$style = $this->twoSecondDataBgLoadCss;
		if(!empty($style)){
			return $style;
		}
		$tagnames = array(['p',''],['p','::before'],['p','::after'],['div',''],['div','::before'],['div','::after'],['section',''],['section','::before'],['section','::after'],['iframelazy',''],['iframe','']);
		$atts = ['#','.'];
		if(!empty($this->addSettings['exclude_ccss_imgs']) && is_array($this->addSettings['exclude_ccss_imgs']) && count($this->addSettings['exclude_ccss_imgs']) > 0){
			foreach ($this->addSettings['exclude_ccss_imgs'] as $selectorGroup) {
				$selectors = preg_split('/\s*,\s*/', (string)$selectorGroup, -1, PREG_SPLIT_NO_EMPTY);
				if (!$selectors) continue;

				foreach ($selectors as $selector) {
					$selector = trim($selector);
					if ($selector === '') continue;
					$parts = preg_split('/\s+/', $selector, -1, PREG_SPLIT_NO_EMPTY);
					if (!$parts) continue;
					$last = array_pop($parts);
					$explode = '';
					if(strpos($last,'#') !== false){
						$explode1 = explode('#',$last);
						$explode = $explode1[0];
					}
					if(strpos($last,'.') !== false){
						$explode2 = explode('.',$last);
						$explode = $explode2[0];
					}
					if(!empty($explode1[0]) && !empty($explode2[0])){
						$explode = strlen($explode1[0]) > strlen($explode2[0]) ? $explode2[0] : $explode1[0];
					}
					if(empty($explode1[1]) && empty($explode2[1])){
						continue;
					}
					if (preg_match('/::?(before|after)\b/i', $last, $m)) {
						$pseudo = '::' . strtolower($m[1]); // ::before or ::after
						$base   = preg_replace('/::?(before|after)\b/i', '', $last); // remove legacy or modern pseudo
					} else {
						$base = $last;
						$pseudo = '';
					}
					if(!empty($explode)){
						$content[$explode.$pseudo][] = $base;
					}else{
						$content['p'.$pseudo][] = $base;
						$content['section'.$pseudo][] = $base;
						$content['div'.$pseudo][] = $base;
					}
				}
			}
		}
		$content = array_merge($content, $this->inlineStyleBackgroundImages);
		foreach($tagnames as $tagname){
			$style .= $tagname[0].'[data-bglz]';
			if(!empty($content[$tagname[0].$tagname[1]])){
				foreach($content[$tagname[0].$tagname[1]] as $tag){
					$style .= ':not('.$tag.')';
				}
			}
			$style .= $tagname[1].',';
		}
		$style = rtrim($style,',').'{background-image:none !important;}';
		$this->twosecond_insert_data_bg_css_in_critical($style);
		return $style;
	}
	/**
	 * Get CSS cache URL for combined files
	 *
	 * Processes CSS files to get their cached URLs and adds them to the
	 * combined CSS files array for optimization.
	 *
	 * @param array $combined_css_files Array of combined CSS files
	 * @param int $key The key/index for the CSS file
	 * @param array $css_obj CSS object containing href and other attributes
	 * @param array $url_array Parsed URL array
	 * @param string $css The original CSS link HTML
	 * @param bool $enable_cdn Whether CDN is enabled
	 * @param bool $enable_cdn_path Whether CDN path is enabled
	 * @return array Updated combined CSS files array
	 */
	public function twosecond_get_css_cache_url( $combined_css_files, $key, $css_obj, $url_array, $css, $enable_cdn, $enable_cdn_path ) {
		$src = $css_obj['href'];
		if(!empty($src) && !$this->twosecond_is_external($src, [], 'css') && $this->twosecond_endswith($src, '.css')){
			$filename = $this->addSettings['root_cache_path'].$url_array['path'];
			$filename1  = $this->addSettings['content_path'].$url_array['path'];
			if(file_exists($filename) && filesize($filename) > 0){
				$combined_css_file = $this->twosecond_get_resource_url($url_array['path'], $enable_cdn && $enable_cdn_path, 0, 'css');
				$this->twosecond_str_replace_set_css($css,'{{'.$combined_css_file.'}}');
				$combined_css_files[$key] = $combined_css_file;
			} else if(file_exists($filename1) && filesize($filename1) > 0){
				$url_array['path'] = $this->twosecond_str_replace_first('/cache/ts-cache', '', $url_array['path']);
				$combined_css_file = $this->twosecond_get_resource_url($url_array['path'], $enable_cdn && $enable_cdn_path, 0, 'css');
				$this->twosecond_str_replace_set_css($css,'{{'.$combined_css_file.'}}');
				$combined_css_files[$key] = $combined_css_file;
			}
		}elseif($this->twosecond_endswith($src, '.css') || strpos($src, '.css?')){
			$this->twosecond_str_replace_set_css($css,'{{'.$css_obj['href'].'}}');
			$combined_css_files[$key] = $css_obj['href'];
		}
		return $combined_css_files;
	}
	/**
	 * Get optimized CSS URL
	 *
	 * Processes CSS objects to get their optimized URLs, handling various
	 * file types and optimization scenarios.
	 *
	 * @param array $css_obj CSS object containing href and other attributes
	 * @param array $url_array Parsed URL array
	 * @param string $css The original CSS link HTML
	 * @param bool $enable_cdn Whether CDN is enabled
	 * @param bool $enable_cdn_path Whether CDN path is enabled
	 * @return array Array containing optimized CSS object, URL array, and return flag
	 */
	public function twosecond_get_optimized_css_url( $css_obj, $url_array, $css, $enable_cdn, $enable_cdn_path ) {
		$return = 0;
		if(!$this->twosecond_is_external($css_obj['href'], [], 'css')){
			// Check if CSS URL has no file extension and remove it
			$root_path = $this->twosecond_get_root_path($url_array['path']);
			if(!$this->twosecond_endswith($css_obj['href'], '.css') && !$this->twosecond_endswith($css_obj['href'], '.php') && strpos($css_obj['href'], '.css?') === false && strpos($css_obj['href'], '.php?') === false){
				$return = 1;
			}elseif($this->twosecond_endswith($css_obj['href'], '.php') || strpos($css_obj['href'], '.php?') !== false ){
				$url_array['path'] = $css_obj['href'];
				$css_obj['href'] = $this->addSettings['site_url'].$url_array['path'];
			}elseif(empty($root_path)){
				if($this->twosecond_endswith($css_obj['href'], '.css') || strpos($css_obj['href'], '.css?') !== false ){
					$this->twosecond_str_replace_set_css($css,'');
					$return = 1;
				}
			}elseif(filesize($root_path.$url_array['path']) > 0){
				$url_array['path'] = $this->twosecond_create_file_cache_css($url_array['path'], $root_path);
				$css_obj['href'] = $this->twosecond_get_resource_url($url_array['path'], $enable_cdn && $enable_cdn_path, 0, 'css');
			}else{
				if($this->twosecond_endswith($css_obj['href'], '.php') || strpos($css_obj['href'], '.php?') !== false || filesize($root_path.$url_array['path']) < 1 ){
					$this->twosecond_str_replace_set_css($css,'');
				}
				$return = 1;
			}
		}elseif(!empty($css_obj['href']) && strpos($css_obj['href'],'fonts.googleapis.com') !== false){
			if(!empty($this->settings['localize_google_fonts'])) {
				$cssArr = $this->twosecond_localize_google_fonts(array($css_obj['href']));
				if(!empty($cssArr) && !empty($cssArr[0]) && strpos($cssArr[0],'/ts-cache/') !== false){
					$url_array['path'] = str_replace($this->addSettings['site_url'],'',$cssArr[0]);
					$url_array['path'] = $this->twosecond_create_file_cache_css($url_array['path']);
					$css_obj['href'] = $this->twosecond_get_resource_url($url_array['path'], $enable_cdn && $enable_cdn_path, 0, 'css');
				}
			}
			
		}elseif(!empty($css_obj['href']) && strpos($css_obj['href'],'/font-awesome/') !== false){
			$css_obj['href'] = $url_array['path'] = $this->twosecond_localize_fonts($css_obj['href']);
		}
		return array($css_obj,$url_array,$return);
	}
	/**
	 * Check if CSS should be excluded from optimization
	 *
	 * Determines whether a CSS file should be excluded from minification
	 * and optimization based on exclusion settings and filters.
	 *
	 * @param array $excludeCssFromMinifyArr Array of CSS exclusions
	 * @param string $css The CSS link HTML
	 * @param array $css_obj CSS object containing href and other attributes
	 * @param array $css_links_arr Array of all CSS links
	 * @param bool $enable_cdn Whether CDN is enabled
	 * @param bool $enable_cdn_path Whether CDN path is enabled
	 * @return bool True if CSS should be excluded, false otherwise
	 */
	public function twosecond_check_css_excluded( $excludeCssFromMinifyArr, $css, $css_obj, $css_links_arr, $enable_cdn, $enable_cdn_path ) {
		$exclude_css = 0;
		if(!empty($excludeCssFromMinifyArr)){
			foreach($excludeCssFromMinifyArr as $ex_css){
				if(!empty($ex_css['string']) && strpos($css, $ex_css['string']) !== false){
					$exclude_css = 1;
					if(!empty($ex_css['cache'])){
						$cache_css = 1;
					}
				}
			}
		}

		$exclude_css = $this->twosecond_exclude_css_filter($exclude_css,$css_obj,$css,$this->html);

		if($exclude_css){
			if($this->twosecond_endswith($css_obj['href'], '.css')){
				if(!empty($cache_css)){
					$url_array = $this->twosecond_parse_css_url($css_obj['href']);
					$url_array['path'] = $this->twosecond_create_file_cache_css($url_array['path']);
					$css_obj['href'] = $url_array['path'];
				}
				$css_obj['href'] = $this->twosecond_get_resource_url($css_obj['href'], $enable_cdn && $enable_cdn_path, 1 && !$cache_css, 'css');
				$this->twosecond_str_replace_set_css($css,'{{'.$css_obj['href'].'}}');
				$this->twosecond_render_css(array($css_obj['href']),$css_links_arr, 1);
			}
			$this->addSettings['preload_resources']['all'][] = $css_obj['href'];
			return true;
		}
		return false;
	}
	
	/**
	 * Check if CSS should be forced to lazy load
	 *
	 * Determines whether a CSS file should be forced to lazy load
	 * based on force lazy load settings.
	 *
	 * @param array $force_lazyload_css Array of CSS files to force lazy load
	 * @param string $css The CSS link HTML
	 * @param array $url_array Parsed URL array
	 * @param array $css_obj CSS object containing href and other attributes
	 * @param array $link_arr Link array containing attributes
	 * @param bool $enable_cdn Whether CDN is enabled
	 * @param bool $enable_cdn_path Whether CDN path is enabled
	 * @return bool True if CSS should be forced to lazy load, false otherwise
	 */
	public function twosecond_check_force_lazy_load( $force_lazyload_css, $css, $url_array, $css_obj, $link_arr, $enable_cdn, $enable_cdn_path ) {
		$force_lazy_load = 0;
		if(!empty($force_lazyload_css)){
			foreach($force_lazyload_css as $ex_css){
				if(!empty($ex_css) && strpos($css, $ex_css) !== false){
					$force_lazy_load = 1;
				}
			}
		}
		if($force_lazy_load){
			list($css_obj,$url_array,$return) = $this->twosecond_get_optimized_css_url($css_obj,$url_array,$css, $enable_cdn, $enable_cdn_path);
			$css_link = $this->twosecond_get_css_attribute($link_arr['arr']);
			$this->twosecond_str_replace_set_css($css,'<link data-href="'.$css_obj['href'].'"'.$css_link.'>');
			return true;
		}
		return false;
	}

	/**
	 * Render CSS links in HTML
	 *
	 * Renders the combined CSS files as HTML link tags and replaces
	 * placeholders in the HTML content.
	 *
	 * @param array $combined_css_files Array of combined CSS files
	 * @param array $css_links_arr Array of CSS link objects
	 * @param int $exclude Whether CSS is excluded (0 = normal, 1 = excluded)
	 */
	public function twosecond_render_css( $combined_css_files, $css_links_arr, $exclude = 0 ) {
		if(!empty($combined_css_files) && is_array($combined_css_files)){
			foreach($combined_css_files as $key=>$css){
				$this->addSettings['preload_resources']['css'][] = $css;
				if(!empty($css_links_arr[$key]['arr']) && count((array)$css_links_arr[$key]['arr']) > 0){
					$css_link = '';
					$css_link = $this->twosecond_get_css_attribute($css_links_arr[$key]['arr']);
				}
				$excludeCss = !$exclude ? ' data-css="1"' : '';
				$this->twosecond_str_replace_set_css('{{'.$css.'}}','<link'.$excludeCss.' href="'.$css.'"'.$css_link.'>');
			}
		}
	}
	/**
	 * Get CSS attributes for link tag
	 *
	 * Extracts and formats CSS attributes from a CSS array,
	 * excluding certain attributes that are handled separately.
	 *
	 * @param array $cssArr Array of CSS attributes
	 * @return string Formatted CSS attributes string
	 */
	public function twosecond_get_css_attribute( $cssArr ) {
		$css_link = '';
		foreach((array)$cssArr as $attr => $attr_value){
			if($attr != 'href' && $attr != 'data-href' && $attr != 'onload' && $attr != 'onerror' && $attr != 'type' && $attr != 'html' ){
				$css_link .= " $attr='$attr_value'";
			}
		}
		return $css_link;
	}
}