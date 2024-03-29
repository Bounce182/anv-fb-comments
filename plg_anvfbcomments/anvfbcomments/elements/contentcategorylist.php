<?php 
/**
 * Joomla! 1.5 plugin. Get content category list. 
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
defined('_JEXEC') or die( 'Restricted access' );

class JElementContentCategoryList extends JElement{

	var	$_name = 'ContentCategoryList';	
	
	/**
	* fetch Element 
	*/
	function fetchElement($name, $value, &$node, $control_name){
		
		$db = &JFactory::getDBO();
		
		// Base name of the HTML control.				
        $ctrl  = $control_name .'['. $name .']';
 
        // Construct an array of the HTML OPTION statements.
        $options = array ();
		
		$query = 'SELECT * FROM #__categories WHERE section > 0';
        $db->setQuery( $query );
		
		$categories = $db->loadObjectList();			
		
		foreach( $categories as $category ){
		  	$val  = $category->id;
			$text = $category->title;
            $options[] = JHTML::_('select.option', $val, $text);
        }
 
        // Construct the various argument calls that are supported.
        $attribs = '';
        
		if ($v = $node->attributes( 'size' )) {
        	$attribs .= ' size="'.$v.'"';
        }
        
		if ($v = $node->attributes( 'class' )) {
        	$attribs .= ' class="'.$v.'"';
        } else {
        	$attribs .= ' class="inputbox"';
        }
        
		if ($m = $node->attributes( 'multiple' )){
        	$attribs .= ' multiple="multiple"';
            $ctrl    .= '[]';
        }
		
		if($s = $node->attributes( 'style' )){
			$attribs	.= ' style="'.$s.'"'; 
		}
 
        // Render the HTML SELECT list.
        return JHTML::_('select.genericlist', $options, $ctrl, $attribs, 'value', 'text', $value, $control_name.$name );
		
	}
	

}

?>
