<?php		
/**
 * Joomla! 1.5 plugin. Helper file.
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
class plgAnvFbCommentsHelper{
	
	function __construct(){
		define( '_JEXEC', 1 ); //let direct access
	
		$basePath = urldecode($_POST['path']);
		
		define('JPATH_BASE', $basePath);
		define('DS', DIRECTORY_SEPARATOR );
		
		//load joomla framework			
		require_once(JPATH_BASE.DS.'administrator'.DS.'includes'.DS.'defines.php' );
		require_once(JPATH_BASE.DS.'administrator'.DS.'includes'.DS.'framework.php');
		require_once(JPATH_LIBRARIES.DS.'joomla'.DS.'factory.php');

		$mainframe =& JFactory::getApplication('site');

		$mainframe->initialise();	
		
		//load plugin language file
		$language =& JFactory::getLanguage();
		$ext 	  = 'plg_content_anvfbcomments';
		$baseDir  = JPATH_ADMINISTRATOR;
		$language->load($ext, $baseDir);
				
	}
	
	function sendNotificationEmail(){
		
		$mailer = JFactory::getMailer();
		
		$commentText	= JRequest::getVar('commentText','','string','post');
		$msgUrl			= urldecode(JRequest::getVar('msgUrl','','string','post'));
		$params 		= JRequest::getVar('params','','string','post');
				
		$params = json_decode($params);
		
		
		if(strlen($params->emails_list) > 0){
			$to = preg_split('/,/',$params->emails_list);
		}
		else{
			return false;	
		}
		
		$conf     =& JFactory::getConfig();
		$siteName = $conf->getValue('config.sitename');
		$date 	  =& JFactory::getDate();
		
		$date     =$date->toFormat();				
		
		$mailer->setSender($params->email_from);
		$mailer->addRecipient($to);				
		
		$subject = sprintf($params->email_subject,$siteName,$date);
								
		$mailer->setSubject($subject);
				
		$bodyHtml = sprintf(JText::_('EMAIL BODY HTML'),$siteName,$msgUrl,$commentText);
		
		$mailer->setBody($bodyHtml);
		$mailer->isHTML(true);
		
		if(!$mailer->send()){
			$response = false;	
		}
		else{
			$response = true;	
		}
		
		
		echo $response;
		
	}
	
}

$helperObj = new plgAnvFbCommentsHelper();

$helperObj->sendNotificationEmail();