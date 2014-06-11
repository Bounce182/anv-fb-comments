<?php
/**
 * Joomla! 1.5 plugin
 *
 * @author ANV
 * @package Joomla
 * @subpackage Plugin ANV Facebook Comments
 * @license GNU/GPL
 *
 * Plugin ANV Facebook comments
 *
 * www.anvweb.com
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgContentAnvFbComments extends JPlugin{

	var $_plugin_params;

 	/**
    * plgAnvFbComments
    *
    * Plugin constructor
    * 
    */
    function plgContentAnvFbComments(&$subject){
		
		parent::__construct($subject);				
		
		// loading plugin parameters
        $plugin = JPluginHelper::getPlugin('content', 'anvfbcomments');
        $params = new JParameter($plugin->params);
		
		$this->_plugin_params['fb_locale']					= $params->get('fb_locale','en_GB');	
		$this->_plugin_params['number_of_posts']    		= $params->get('number_of_posts',10);
		$this->_plugin_params['comments_form_width']    	= $params->get('comments_form_width',400);
		$this->_plugin_params['color_scheme']    			= $params->get('color_scheme','light');
		$this->_plugin_params['fb_admin_ids']    			= $params->get('fb_admin_ids','');
		$this->_plugin_params['fb_app_id']    				= $params->get('fb_app_id','');
		$this->_plugin_params['top_divider']    			= $params->get('top_divider','');
		$this->_plugin_params['bot_divider']    			= $params->get('bot_divider','');
		$this->_plugin_params['content_section_list']    	= $params->get('content_section_list','');
		$this->_plugin_params['content_category_list']    	= $params->get('content_category_list','');
		$this->_plugin_params['show_on_uncategorised']    	= $params->get('show_on_uncategorised','');
		$this->_plugin_params['ban_on_frontpage']    		= $params->get('ban_on_frontpage',1);
    	$this->_plugin_params['show_comments_count']  		= $params->get('show_comments_count',0);
		$this->_plugin_params['left_divider']  				= $params->get('left_divider','');
		$this->_plugin_params['right_divider']  			= $params->get('right_divider','');
		$this->_plugin_params['send_notifications']			= $params->get('send_notifications',0);
		$this->_plugin_params['emails_list']				= $params->get('emails_list','');
		$this->_plugin_params['email_subject']				= $params->get('email_subject','');
		$this->_plugin_params['email_from']					= $params->get('email_from','');
		$this->_plugin_params['seo_boost']					= $params->get('seo_boost',1);
		$this->_plugin_params['comments_output_title']		= $params->get('comments_output_title','Anv Facebook comments');
		$this->_plugin_params['hide_from_visitors']			= $params->get('hide_from_visitors',0);
		$this->_plugin_params['time_format']				= $params->get('time_format','l jS \of F Y h:i:s A');
		$this->_plugin_params['cache']						= $params->get('cache',0);
		$this->_plugin_params['cache_time']					= $params->get('cache_time',900);			
		$this->_plugin_params['like_button']				= $params->get('like_button',1);
		$this->_plugin_params['send_button']				= $params->get('send_button',1);
		$this->_plugin_params['layout_style']				= $params->get('layout_style','standart');
		$this->_plugin_params['like_button_width']			= $params->get('like_button_width',400);
		$this->_plugin_params['show_faces']					= $params->get('show_faces',0);
		$this->_plugin_params['verb_to_display']			= $params->get('verb_to_display','like');		
		$this->_plugin_params['font']						= $params->get('cache_time','');		
		$this->_plugin_params['show_copyright']				= $params->get('show_copyright',1);
		$this->_plugin_params['copyright_line']				= $params->get('copyright_line','');
		
		//preload primary data		
		$this->_preloadPrimaryData();
		
	}
	
	/**
	* Show Facebook comments plugin in articles.
	*
	* @param object, content item
	* @param array, content params
	* @param int, page number
	*/
	function onPrepareContent(&$article, &$params, $limitstart){													
				
		$view   = JRequest::getVar('view','','request','string');
				
		$url    = $this->_getArticleUrl($article->id,$view);	
		
		//add facebook tags. do not use these tags on frontpage
		if($view == "article" && $view != "frontpage"){
			$this->_addFbCustomTags($article,$url);						
		}
		
		//show comments count ?
		if($this->_plugin_params['show_comments_count']){
			
			//change category to article								
			$url = str_replace('category','article',$url);			
			
			$commentsCount   = $this->_getFbCommentsCount(urlencode($url));
			if($view != 'article'){	
				$article->title .= $this->_plugin_params['left_divider'].$commentsCount.$this->_plugin_params['right_divider'];	
			}
		}				
		

		//no article id - return
		if(!isset($article->id) || ($view != "article" && $view != "frontpage")){
			return;	
		}	
		
		//if do not show banners on front page
		if( $view == 'frontpage' && $this->_plugin_params['ban_on_frontpage'] == 0 ){
			return;	
		}
		
		//show on uncategorised articles ?
		if($this->_plugin_params['show_on_uncategorised']){
			if(is_array($this->_plugin_params['content_section_list'])){
				$this->_plugin_params['content_section_list'][]  = 0;
				$this->_plugin_params['content_category_list'][] = 0;	
			}
			else{
				//lets make it array
				$section_list = $this->_plugin_params['content_section_list'];
				$cat_list	  = $this->_plugin_params['content_category_list'];
				
				$this->_plugin_params['content_section_list']  = array();
				$this->_plugin_params['content_category_list'] = array() ;
				
				$this->_plugin_params['content_section_list'][]  = 0;
				$this->_plugin_params['content_category_list'][] = 0;
				$this->_plugin_params['content_section_list'][]  = $section_list;
				$this->_plugin_params['content_category_list'][] = $cat_list;		
			}
		}
		
		//check sections		
		if( is_array($this->_plugin_params['content_section_list'])){		//if selected more than one section
			if( !in_array( $article->sectionid, $this->_plugin_params['content_section_list'] )){	
				return;
			}
		}
		else{								//if only one item is selected	
			if( $article->sectionid != $this->_plugin_params['content_section_list'] ){					
				return;
			}
		}
		
		//check categories		
		if( is_array($this->_plugin_params['content_category_list'])){		//if selected more than one category
			if( !in_array( $article->catid, $this->_plugin_params['content_category_list'] )){				
				return;
			}
		}
		else{								//if only one item is selected	
			if( $article->catid != $this->_plugin_params['content_category_list'] ){				
				return;
			}
		}
		
		//inserting like button
		if($this->_plugin_params['like_button']){
			$article->text .= '<div class="fb-like" data-href="'.$url.'" data-send="'.
							  $this->_plugin_params['send_button'].
							  '" data-layout="'.
							  $this->_plugin_params['layout_style'].'" '.
							  'data-width="'.$this->_plugin_params['like_button_width'].
							  '" data-show-faces="'.$this->_plugin_params['show_faces'].
							  '" data-action="'.$this->_plugin_params['verb_to_display'].
							  '" data-colorscheme="'.$this->_plugin_params['color_scheme'].
							  '" data-font="'.$this->_plugin_params['font'].
							  '"></div>';
		}
		
		//inserting plugin to article html
		$article->text .= $this->_getPlugin($url);
		
		//if seo boost enabled
		if($this->_plugin_params['seo_boost']){												
			//set plugins comment style
			JHTML::stylesheet('style.css', 'plugins/content/anvfbcomments/assets/css/');
			$article->text .= $this->_getCommentsFromFb($url);															
		}
		
		//show copyright
		if($this->_plugin_params['show_copyright']){
			$article->text .= $this->_plugin_params['copyright_line'];
		}
							
	}
	
	/**
	* Get article url
	*
	* @param id, article id (default null)
	* @param string, view
	* @return string, article url
	*/
	function _getArticleUrl($artId = null,$view){
		
		$url = substr_replace(JURI::base(),"",-1);

		$url = $url.JRoute::_('index.php?option=com_content&view='.$view.'&id='.$artId);					
		
		return $url;
	}
		
	/**
	* Get Facebook comments count from facebook graph
	*
	* @param string, article url
	* @return int, comments count
	*/
	function _getFbCommentsCount($url){		
		
		$pageContent = file_get_contents('http://graph.facebook.com/?ids='.$url);
		$parsedJson  = json_decode($pageContent);	
				
		foreach($parsedJson as $key => $value){
			
			if(isset($value->comments)){
				$comments = $value->comments;	
			}
			else{
				$comments = 0;	
			}
		}
		
		return $comments;	
	}
	
	/**
	* Get plugin
	*
	* @param string, article url
	* @return string, plugins code
	*/
	function _getPlugin($url){
		
		$plugin = '';		
			
		$plugin .= $this->_plugin_params['top_divider'].'<div class="fb-comments" data-href="'.$url.'" data-num-posts="'
		.$this->_plugin_params['number_of_posts'].'" data-width="'.$this->_plugin_params['comments_form_width'].
		'" colorscheme="'.$this->_plugin_params['color_scheme'].'" notify="true"></div>'.$this->_plugin_params['bot_divider'];		
		
		return $plugin;
	}
	
	/**
	* Get comments from facebook (making it crawlable)
	*
	* @param string, article url
	* @return string, formated comments
	*/
	function _getCommentsFromFb($url){
		
		//cache comments?		
		if($this->_plugin_params['cache']){
		
			$cache 	  = & JFactory::getCache();			
			
			//if global caching is turned off, it will take these settings
			if($this->_plugin_params['cache'] == 1){
				$cache->setCaching(1);	
				$cache->setLifeTime($this->_plugin_params['cache_time']);
			}
			
			$result   = $cache->call(array($this,'_getFbCommentsObj'),$url);
		}
		else{
			$result   = $this->_getFbCommentsObj($url);	
		}

		$comments 	  = $result[0]->fql_result_set;
		$replies 	  = $result[1]->fql_result_set;
		$profiles 	  = $result[2]->fql_result_set;
		
		$profilesById = array();
	
		foreach ($profiles as $profile) {
	  		$profilesById[$profile->id] = $profile;
		}
		
		$repliesByTarget = array();
		
		foreach ($replies as $reply) {
	  		$repliesByTarget[$reply->object_id][] = $reply;
		}
		
		$commentsStr = '';
		
		if(sizeof($comments) > 0){
			
			if($this->_plugin_params['hide_from_visitors']){
				$styleDisplayNone = ' style="display:none;" ';
			}
			else{
				$styleDisplayNone = '';	
			}
			
			$commentsStr .= '<div id="pafc_comments" '.$styleDisplayNone.'><h3>'.$this->_plugin_params['comments_output_title'].'</h3>';		
			
			foreach ($comments as $comment){
				$commentsStr .= '<div class="pafc_fbcomments">';
				$commentsStr .= $this->_formatComment($comment, $profilesById);
				$commentsStr .= '</div>';
				
				//if there are replies so get it
				if (!empty($repliesByTarget[$comment->post_fbid])) {
					foreach ($repliesByTarget[$comment->post_fbid] as $reply){
						$commentsStr .= '<div class="pafc_fbcomments_reply">';
						$commentsStr .= $this->_formatComment($reply, $profilesById);
						$commentsStr .= '</div>';
					}
				}
			}
			
			$commentsStr .='</div>';		
		}
		
		return $commentsStr;
	}
	
	/**
	* Get Facebook comments object
	*
	* @param string, article url
	* @return object, facebook comments object
	*/
	function _getFbCommentsObj($url){
		
		// making one FQL query to get all the data
		$queries = array('q1' => 'select post_fbid, fromid, object_id, text, time from comment where object_id in '.
					             '(select comments_fbid from link_stat where url ="'.$url.'")',
					 	 'q2' => 'select post_fbid, fromid, object_id, text, time from comment where object_id in (select post_fbid from #q1)',
					 	 'q3' => 'select name, id, url, pic_square from profile where id in (select fromid from #q1) or id in (select fromid from #q2)',
					    );	
	
		$result = json_decode(file_get_contents('http://api.facebook.com/restserver.php?format=json-strings&method=fql.multiquery&queries='.
												urlencode(json_encode($queries))));
												
		return $result;													
	}	
	
	/**
	* Make facebook comment formated
	*
	* @param object, comment
	* @param array, list of profiles
	* @return string, formated comments
	*/
	function _formatComment($comment, $profilesById){
				
		$profile = $profilesById[$comment->fromid];
	  	
		$authorMarkup = '';
	  
	 	if($profile){
			$authorMarkup .='<span class="pafc_profile">';
			$authorMarkup .='<img class="pafc_photo" src="'.$profile->pic_square.'" align="left" />';
			$authorMarkup .='<a class="pafc_name" href="'.$profile->url.'" target="_blank">'.$profile->name.'</a>';
		  	$authorMarkup .='</span>';
	  	}		
	  	
		$res = '';
	  
	    $res .= $authorMarkup.'<div class="pafc_date">&nbsp;'.date($this->_plugin_params['time_format'],$comment->time).'</div>';
		$res .='<p>'.$comment->text.'</p>';	
		
		return $res;
	}
	
	/**
	* Preload primary data for plugin constructor
	*
	* @return boolean, true
	*/
	function _preloadPrimaryData(){
		
		$doc =& JFactory::getDocument();
		
		//add custom tags
		$metaTag1 = '<meta name="fb-admins" property="fb:admins" content="'.$this->_plugin_params['fb_admin_ids'].'" />';
		$metaTag2 = '<meta name="fb-app-admin" property="fb:app_id" content="'.$this->_plugin_params['fb_app_id'].'" />';		
		
		$doc->addCustomTag($metaTag1);
		$doc->addCustomTag($metaTag2);
		
		//add facebook main script
		$script = '
			(function(d, s, id) {
  				var js, fjs = d.getElementsByTagName(s)[0];
  				if (d.getElementById(id)) return;
  				js = d.createElement(s); js.id = id;
  				js.src = "//connect.facebook.net/'.$this->_plugin_params['fb_locale'].'/all.js#xfbml=1&appId='.$this->_plugin_params['fb_app_id'].'";
  				fjs.parentNode.insertBefore(js, fjs);
			}(document, \'script\', \'facebook-jssdk\'));
				
		';
			
		$doc->addScriptDeclaration($script);	
		
		//add fb-root tag to body only once
		echo '<div id="fb-root"></div>'; 
		
		//if send notifications		
		if($this->_plugin_params['send_notifications']){
			
			JHTML::_('behavior.mootools');
			
			$params = json_encode($this->_plugin_params);
			
			$path = urlencode(JPATH_BASE.DS);
			
			$script ='window.fbAsyncInit = function(){
  							FB.Event.subscribe("comment.create", function(response){	
									
								var commentQuery = FB.Data.query("SELECT text FROM comment WHERE post_fbid=\'" + response.commentID +
                				"\' AND object_id IN (SELECT comments_fbid FROM link_stat WHERE url=\'" + response.href + "\')");
									
								FB.Data.waitOn([commentQuery], function (){
									var commentRow = commentQuery.value[0];	
									var ajaxUrl = "'.JURI::base().'/plugins/content/anvfbcomments/helpers/helper.php";
								    new Ajax(ajaxUrl, {method: "post",data:{commentText:commentRow.text,msgUrl:response.href,params:\''.$params.'\'
									,path:"'.$path.'"}}).request();
								});															
									
  							});
					  };									
			';
			
			$doc->addScriptDeclaration($script);			
		}
		
		return true;
	}	
	
	/* Get article image from article HTML
	*
	* @param string, articles html		
	* @return string, image path
	*/  
	function _getArticleImage($html){		
				
		//get src 	
		preg_match('/src=[\'"]?([^\'" >]+)[\'" >]/',$html,$matches);		
		$imgSrc = isset($matches[1]) ? $matches[1] : '';
		
		//remove unwanted characters
		$imgSrc = str_replace('"','',$imgSrc);
						
		return $imgSrc;		
	}
	
	/* Remove tags and make text shorter for facebook description tag
	*
	* @param string, articles html		
	* @return string, prepared text
	*/  
	function _prepareArticleTextForFb($html){
		$text = strip_tags($html);
		
		if(strlen($text) > 300){
			$text = substr($text,0,300).'...';	
		}
		
		return $text;
	}
	
	/* Add Facebook custom tags
	*
	* @param object, articles object
	* @param string, artile url		
	* @return boolean, true
	*/  
	function _addFbCustomTags($article,$url){
		
		$doc    	=& JFactory::getDocument();		
		$config 	=& JFactory::getConfig();
		
		$metaTag1 	= '<meta property="og:title" content="'.$article->title.'" />';
		$metaTag2 	= '<meta property="og:url" content="'.$url.'" />';
		
		$image    	= JURI::base().$this->_getArticleImage($article->text);		
		
		$metaTag3 	= '<meta property="og:image" content="'.$image.'" />';
		$metaTag4 	= '<meta property="og:site_name" content="'.$config->getValue( 'config.sitename' ).'" />';
		
		$articleDesc = $this->_prepareArticleTextForFb($article->text);   
		
		$metaTag5 	 = '<meta property="og:description" content="'.$articleDesc.'" />';
		
		$doc->addCustomTag($metaTag1);
		$doc->addCustomTag($metaTag2);
		$doc->addCustomTag($metaTag3);
		$doc->addCustomTag($metaTag4);
		$doc->addCustomTag($metaTag5);	
	}
	
}

?>