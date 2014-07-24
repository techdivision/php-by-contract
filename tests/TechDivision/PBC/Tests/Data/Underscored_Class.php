<?php

namespace Random\Test\NamespaceName;

/**
 *
 */
class Underscored_Class
{

    /**
     * @var array $classmap The defined classes
     * @access private
     */
    private static $classmap = array();

    /**
     * @param array $options A array of config values
     * @param string $wsdl The wsdl file to use
     * @access public
     */
    public function __construct(array $options = array(), $wsdl = '')
    {
      foreach (self::$classmap as $key => $value) {
        if (!isset($options['classmap'][$key])) {
          $options['classmap'][$key] = $value;
        }
      }
      
      parent::__construct($wsdl, $options);
    }

    /**
     * @param Test $parameters
     * @access public
     * @return TestResponse
     */
    public function Test(Test $parameters)
    {
      return $this->__soapCall('Test', array($parameters));
    }

    /**
     * @param TestValidate $parameters
     * @access public
     * @return TestValidateResponse
     */
    public function TestValidate(TestValidate $parameters)
    {
      return $this->__soapCall('TestValidate', array($parameters));
    }

    /**
     * @param GetResources $parameters
     * @access public
     * @return GetResourcesResponse
     */
    public function GetResources(GetResources $parameters)
    {
      return $this->__soapCall('GetResources', array($parameters));
    }

    /**
     * @param HasUserSetting $parameters
     * @access public
     * @return HasUserSettingResponse
     */
    public function HasUserSetting(HasUserSetting $parameters)
    {
      return $this->__soapCall('HasUserSetting', array($parameters));
    }

    /**
     * @param GetUserSetting $parameters
     * @access public
     * @return GetUserSettingResponse
     */
    public function GetUserSetting(GetUserSetting $parameters)
    {
      return $this->__soapCall('GetUserSetting', array($parameters));
    }

    /**
     * @param SaveUserSetting $parameters
     * @access public
     * @return SaveUserSettingResponse
     */
    public function SaveUserSetting(SaveUserSetting $parameters)
    {
      return $this->__soapCall('SaveUserSetting', array($parameters));
    }

    /**
     * @param SaveUserSettings $parameters
     * @access public
     * @return SaveUserSettingsResponse
     */
    public function SaveUserSettings(SaveUserSettings $parameters)
    {
      return $this->__soapCall('SaveUserSettings', array($parameters));
    }

    /**
     * @param GetSpecimenBook $parameters
     * @access public
     * @return GetSpecimenBookResponse
     */
    public function GetSpecimenBook(GetSpecimenBook $parameters)
    {
      return $this->__soapCall('GetSpecimenBook', array($parameters));
    }

    /**
     * @param GetSpecimenBookText $parameters
     * @access public
     * @return GetSpecimenBookTextResponse
     */
    public function GetSpecimenBookText(GetSpecimenBookText $parameters)
    {
      return $this->__soapCall('GetSpecimenBookText', array($parameters));
    }

    /**
     * @param SaveSpecimenBook $parameters
     * @access public
     * @return SaveSpecimenBookResponse
     */
    public function SaveSpecimenBook(SaveSpecimenBook $parameters)
    {
      return $this->__soapCall('SaveSpecimenBook', array($parameters));
    }

    /**
     * @param GetRecipientBook $parameters
     * @access public
     * @return GetRecipientBookResponse
     */
    public function GetRecipientBook(GetRecipientBook $parameters)
    {
      return $this->__soapCall('GetRecipientBook', array($parameters));
    }

    /**
     * @param SaveRecipientBook $parameters
     * @access public
     * @return SaveRecipientBookResponse
     */
    public function SaveRecipientBook(SaveRecipientBook $parameters)
    {
      return $this->__soapCall('SaveRecipientBook', array($parameters));
    }

    /**
     * @param LogUserAction $parameters
     * @access public
     * @return LogUserActionResponse
     */
    public function LogUserAction(LogUserAction $parameters)
    {
      return $this->__soapCall('LogUserAction', array($parameters));
    }

}
