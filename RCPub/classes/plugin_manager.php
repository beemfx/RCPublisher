<?php

interface IPlugin
{
	function GetType();
	function GetName();
	function GetSettings();
	function Render();
}

class CPluginManager
{
	private $m_Plugins = array();
	
	function CPluginManager()
	{
		
	}
	
	public function RegisterPlugin( $Name )
	{
		require( 'plugins/plugin.'.$Name.'.php' );
		$ClassName = $Name.'_Plugin';
		$PluginClass = new $ClassName();
		assert( $PluginClass instanceof IPlugin );
		
		$this->m_Plugins[$Name] = $PluginClass;
	}
	
	public function GetPluginByName( $Name )
	{
		assert( 'string' == gettype( $Name ) );
		assert( isset($this->m_Plugins[$Name]) );
		return $this->m_Plugins[$Name];
	}
	
	public function GetPluginCount()
	{
		return count( $this->m_Plugins );
	}
	
	public function GetPluginByIndex( $Index )
	{
		assert( 'integer' == gettype( $Index ) && $Index < $this->GetPluginCount() );
		$IndexArray = array_values( $this->m_Plugins );
		return $IndexArray[$Index];
	}
}

$PluginManager_Instance = null;

function PluginManager_Init()
{
	global $PluginManager_Instance;
	assert( null == $PluginManager_Instance );
	$PluginManager_Instance = new CPluginManager();
}

function PluginManager_Deinit()
{
	global $PluginManager_Instance;
	assert( null != $PluginManager_Instance );
	$PluginManager_Instance = null;
}

function PluginManager_GetInstance()
{
	global $PluginManager_Instance;
	assert( null != $PluginManager_Instance );
	
	return $PluginManager_Instance;
}

?>