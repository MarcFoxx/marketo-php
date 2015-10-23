<?php

namespace Marketo;

class Marketo
{

    const CLIENT_TZ = 'America/Los_Angeles';

    const MKTOWS_NAMESPACE = 'http://www.marketo.com/mktows/';

    protected $accessKey;

    protected $secretKey;

    protected $url;

    protected $soapClient;

    public function __construct($config = array())
    {
        if (isset($config['accessKey'])) $this->accessKey = $config['accessKey'];
        if (isset($config['secretKey'])) $this->secretKey = $config['secretKey'];
        if (isset($config['soapEndPoint'])) $this->url = $config['soapEndPoint'];


        $options = array("trace" => true, "connection_timeout" => 20, "location" => $this->url);
        $wsdlUri = $this->url . '?WSDL';
        $this->soapClient = new \SoapClient($wsdlUri, $options);

    }


    static public function newAttribute($name, $value)
    {
        $attr = new Attribute();
        $attr->attrName = $name;
        $attr->attrValue = $value;
        return $attr;
    }

    private function _getAuthenticationHeader($paramName)
    {
        $dtzObj = new \DateTimeZone(self::CLIENT_TZ);
        $dtObj = new \DateTime('now', $dtzObj);
        $timestamp = $dtObj->format(DATE_W3C);
        $encryptString = $timestamp . $this->accessKey;
        $signature = hash_hmac('sha1', $encryptString, $this->secretKey);
        $attrs = new \stdClass();
        $attrs->mktowsUserId = $this->accessKey;
        $attrs->requestSignature = $signature;
        $attrs->requestTimestamp = $timestamp;
        $soapHdr = new \SoapHeader(self::MKTOWS_NAMESPACE, 'AuthenticationHeader', $attrs);
        return $soapHdr;
    }

    private function _getAuthenticationHeaderWithContext($paramName, $contextName = NULL)
    {
        $dtzObj = new \DateTimeZone(self::CLIENT_TZ);
        $dtObj = new \DateTime('now', $dtzObj);
        $timestamp = $dtObj->format(DATE_W3C);
        $encryptString = $timestamp . $this->accessKey;
        $signature = hash_hmac('sha1', $encryptString, $this->secretKey);
        $attrs = new \stdClass();
        $attrs->mktowsUserId = $this->accessKey;
        $attrs->requestSignature = $signature;
        $attrs->requestTimestamp = $timestamp;
        $context = new \stdClass();
        $context->targetWorkspace = $contextName;
        $soapHdr = array();
        $soapHdr[] = new \SoapHeader(self::MKTOWS_NAMESPACE, 'MktowsContextHeader', $context);
        $soapHdr[] = new \SoapHeader(self::MKTOWS_NAMESPACE, 'AuthenticationHeader', $attrs);
        return $soapHdr;
    }

    public function getLead($keyType, $keyValue)
    {
        $success = false;
        $leadKey = new LeadKey();
        $leadKey->keyType = $keyType;
        $leadKey->keyValue = $keyValue;
        $params = new paramsGetLead();
        $params->leadKey = $leadKey;
        $options = array();
        $authHdr = $this->_getAuthenticationHeader('paramsGetLead');
        try {
            $success = $this->soapClient->__soapCall('getLead', array($params), $options, $authHdr);
            $resp = $this->soapClient->__getLastResponse();
        } catch (SoapFault $ex) {
            $ok = false;
            $errCode = 1;
            $faultCode = null;
            if (!empty($ex->detail->serviceException->code)) {
                $errCode = $ex->detail->serviceException->code;
            }
            if (!empty($ex->faultCode)) {
                $faultCode = $ex->faultCode;
            }
            switch ($errCode) {
                case mktWsError::ERR_LEAD_NOT_FOUND:
                    $ok = true;
                    $success = false;
                    break;
                default:
            }
            if (!$ok) {
                if ($faultCode != null) {
                    if (strpos($faultCode, 'Client')) {
                    } else if (strpos($faultCode, 'Server')) {
                    } else {
                    }
                } else {
                }
            }
        } catch (Exception $ex) {
            $msg = $ex->getMessage();
            $req = $this->soapClient->__getLastRequest();
            var_dump($ex);
            exit(1);
        }
        return $success;
    }

    public function syncLead($leadId, $email, $marketoCookie, $attrs, $contextName = false)
    {
        $attrArray = array();
        foreach ($attrs as $attrName => $attrValue) {
            $a = new Attribute();
            $a->attrName = $attrName;
            $a->attrValue = $attrValue;
            $attrArray[] = $a;
        }
        $aryOfAttr = new ArrayOfAttribute();
        $aryOfAttr->attribute = $attrArray;
        $leadRec = new LeadRecord();
        $leadRec->leadAttributeList = $aryOfAttr;
        if (!empty($leadId)) {
            $leadRec->Id = $leadId;
        }
        if (!empty($email)) {
            $leadRec->Email = $email;
        }
        $params = new paramsSyncLead();
        $params->leadRecord = $leadRec;
        $params->returnLead = true;
        $params->marketoCookie = $marketoCookie;
        $options = array();
        if ($contextName) {
            $authHdr = $this->_getAuthenticationHeaderWithContext('paramsGetLead', $contextName);
        } else {
            $authHdr = $this->_getAuthenticationHeader('paramsGetLead');
        }
        $this->soapClient->__setSoapHeaders($authHdr);
        try {
            $success = $this->soapClient->__soapCall('syncLead', array($params), $options);
            $resp = $this->soapClient->__getLastResponse();
        } catch (SoapFault $ex) {
            $ok = false;
            $errCode = 1;
            $faultCode == null;
            if (!empty($ex->detail->serviceException->code)) {
                $errCode = $ex->detail->serviceException->code;
            }
            if (!empty($ex->faultCode)) {
                $faultCode = $ex->faultCode;
            }
            switch ($errCode) {
                case mktWsError::ERR_LEAD_SYNC_FAILED:
                    break;
                default:
            }
            if (!$ok) {
                if ($faultCode != null) {
                    if (strpos($faultCode, 'Client')) {
                    } else if (strpos($faultCode, 'Server')) {
                    } else {
                    }
                } else {
                }
            }
        } catch (Exception $ex) {
            $msg = $ex->getMessage();
            $req = $this->soapClient->__getLastRequest();
            var_dump($ex);
            exit(1);
        }
        return $success;
    }

    public function requestCampaign($campId, $leadEmail)
    {
        $retStat = false;

        $leadKey = new LeadKey();
        $leadKey->keyType = 'IDNUM';
        $leadKey->keyValue = $leadEmail;

        $leadList = new ArrayOfLeadKey();
        $leadList->leadKey = array($leadKey);

        $params = new paramsRequestCampaign();
        $params->campaignId = $campId;
        $params->leadList = $leadList;
        $params->source = 'MKTOWS';

        $authHdr = $this->_getAuthenticationHeader('paramsRequestCampaign');

        try {
            $success = $this->soapClient->__soapCall('requestCampaign', array($params), $options, $authHdr);

            if (isset($success->result->success)) {
                $retStat = $success->result->success;
            }
        } catch (SoapFault $ex) {
            $ok = false;
            $errCode = !empty($ex->detail->serviceException->code) ? $ex->detail->serviceException->code : 1;
            $faultCode = !empty($ex->faultCode) ? $ex->faultCode : null;
            switch ($errCode) {
                case mktWsError::ERR_LEAD_NOT_FOUND:
                    // Handle error for campaign not found
                    break;
                default:
                    // Handle other errors
            }
            if (!$ok) {
                if ($faultCode != null) {
                    if (strpos($faultCode, 'Client')) {
                        // This is a client error.  Check the other codes and handle.
                    } else if (strpos($faultCode, 'Server')) {
                        // This is a server error.  Call Marketo support with details.
                    } else {
                        // W3C spec has changed <img src="http://ahmeddirie.com/wp-includes/images/smilies/icon_smile.gif" alt=":)" class="wp-smiley">
                        // But seriously, Call Marketo support with details.
                    }
                } else {
                    // Not a good place to be.
                }
            }
        } catch (Exception $ex) {
            $msg = $ex->getMessage();
            $req = $this->soapClient->__getLastRequest();
            echo "Error occurred for request: $msg\n$req\n";
            var_dump($ex);
            exit(1);
        }
        return $retStat;
    }
}

class mktWsError
{
    const ERR_SEVERE_INTERNAL_ERROR = 10001;
    const ERR_INTERNAL_ERROR = 20011;
    const ERR_REQUEST_NOT_UNDERSTOOD = 20012;
    const ERR_ACCESS_DENIED = 20013;
    const ERR_AUTH_FAILED = 20014;
    const ERR_REQUEST_LIMIT_EXCEEDED = 20015;
    const ERR_REQ_EXPIRED = 20016;
    const ERR_INVALID_REQ = 20017;
    const ERR_LEAD_KEY_REQ = 20101;
    const ERR_LEAD_KEY_BAD = 20102;
    const ERR_LEAD_NOT_FOUND = 20103;
    const ERR_LEAD_DETAIL_REQ = 20104;
    const ERR_LEAD_ATTRIB_BAD = 20105;
    const ERR_LEAD_SYNC_FAILED = 20106;
    const ERR_PARAMETER_REQ = 20109;
    const ERR_PARAMETER_BAD = 20110;
}

class ActivityRecord
{
    public $id;
    public $activityDateTime;
    public $activityType;
    public $mktgAssetName;
    public $activityAttributes;
    public $campaign;
    public $personName;
    public $mktPersonId;
    public $foreignSysId;
    public $orgName;
    public $foreignSysOrgId;
}

class ActivityTypeFilter
{
    public $includeTypes;
    public $excludeTypes;
}

class Attribute
{
    public $attrName;
    public $attrType;
    public $attrValue;
}

class AuthenticationHeaderInfo
{
    public $mktowsUserId;
    public $requestSignature;
    public $requestTimestamp;
    public $audit;
    public $mode;
}

class CampaignRecord
{
    public $id;
    public $name;
    public $description;
}

class LeadActivityList
{
    public $returnCount;
    public $remainingCount;
    public $newStartPosition;
    public $activityRecordList;
}

class LeadChangeRecord
{
    public $id;
    public $activityDateTime;
    public $activityType;
    public $mktgAssetName;
    public $activityAttributes;
    public $campaign;
    public $mktPersonId;
}

class LeadKey
{
    public $keyType;
    public $keyValue;
}

class LeadRecord
{
    public $Id;
    public $Email;
    public $leadAttributeList;
}

class LeadStatus
{
    public $leadKey;
    public $status;
}

class ListKey
{
    public $keyType;
    public $keyValue;
}

class ResultGetCampaignsForSource
{
    public $returnCount;
    public $campaignRecordList;
}

class ResultGetLead
{
    public $count;
    public $leadRecordList;
}

class ResultGetLeadChanges
{
    public $returnCount;
    public $remainingCount;
    public $newStartPosition;
    public $leadChangeRecordList;
}

class ResultListOperation
{
    public $success;
    public $statusList;
}

class ResultRequestCampaign
{
    public $success;
}

class ResultSyncLead
{
    public $leadId;
    public $syncStatus;
    public $leadRecord;
}

class StreamPosition
{
    public $latestCreatedAt;
    public $oldestCreatedAt;
    public $activityCreatedAt;
    public $offset;
}

class ArrayOfActivityRecord
{
    public $activityRecord;
}

class ArrayOfActivityType
{
    public $activityType;
}

class ArrayOfAttribute
{
    public $attribute;
}

class ArrayOfBase64Binary
{
    public $base64Binary;
    public $base64Binary_encoded;
}

class ArrayOfCampaignRecord
{
    public $campaignRecord;
}

class ArrayOfLeadChangeRecord
{
    public $leadChangeRecord;
}

class ArrayOfLeadKey
{
    public $leadKey;
}

class ArrayOfLeadRecord
{
    public $leadRecord;
}

class ArrayOfLeadStatus
{
    public $leadStatus;
}

class paramsGetCampaignsForSource
{
    public $source;
    public $name;
    public $exactName;
}

class paramsGetLead
{
    public $leadKey;
}

class paramsGetLeadActivity
{
    public $leadKey;
    public $activityFilter;
    public $startPosition;
    public $batchSize;
}

class paramsGetLeadChanges
{
    public $startPosition;
    public $activityFilter;
    public $batchSize;
}

class paramsListOperation
{
    public $listOperation;
    public $listKey;
    public $listMemberList;
    public $strict;
}

class paramsRequestCampaign
{
    public $source;
    public $campaignId;
    public $leadList;
}

class paramsSyncLead
{
    public $leadRecord;
    public $returnLead;
    public $marketoCookie;
}

class successGetCampaignsForSource
{
    public $result;
}

class successGetLead
{
    public $result;
}

class successGetLeadActivity
{
    public $leadActivityList;
}

class successGetLeadChanges
{
    public $result;
}

class successListOperation
{
    public $result;
}

class successRequestCampaign
{
    public $result;
}

class successSyncLead
{
    public $result;
}

class MktowsXmlSchema
{
    static public
        $class_map = array(
        "ActivityRecord" => "ActivityRecord",
        "ActivityTypeFilter" => "ActivityTypeFilter",
        "Attribute" => "Attribute",
        "AuthenticationHeaderInfo" => "AuthenticationHeaderInfo",
        "CampaignRecord" => "CampaignRecord",
        "LeadActivityList" => "LeadActivityList",
        "LeadChangeRecord" => "LeadChangeRecord",
        "LeadKey" => "LeadKey",
        "LeadRecord" => "LeadRecord",
        "LeadStatus" => "LeadStatus",
        "ListKey" => "ListKey",
        "ResultGetCampaignsForSource" => "ResultGetCampaignsForSource",
        "ResultGetLead" => "ResultGetLead",
        "ResultGetLeadChanges" => "ResultGetLeadChanges",
        "ResultListOperation" => "ResultListOperation",
        "ResultRequestCampaign" => "ResultRequestCampaign",
        "ResultSyncLead" => "ResultSyncLead",
        "StreamPosition" => "StreamPosition",
        "ArrayOfActivityRecord" => "ArrayOfActivityRecord",
        "ArrayOfActivityType" => "ArrayOfActivityType",
        "ArrayOfAttribute" => "ArrayOfAttribute",
        "ArrayOfBase64Binary" => "ArrayOfBase64Binary",
        "ArrayOfCampaignRecord" => "ArrayOfCampaignRecord",
        "ArrayOfLeadChangeRecord" => "ArrayOfLeadChangeRecord",
        "ArrayOfLeadKey" => "ArrayOfLeadKey",
        "ArrayOfLeadRecord" => "ArrayOfLeadRecord",
        "ArrayOfLeadStatus" => "ArrayOfLeadStatus",
        "paramsGetCampaignsForSource" => "paramsGetCampaignsForSource",
        "paramsGetLead" => "paramsGetLead",
        "paramsGetLeadActivity" => "paramsGetLeadActivity",
        "paramsGetLeadChanges" => "paramsGetLeadChanges",
        "paramsListOperation" => "paramsListOperation",
        "paramsRequestCampaign" => "paramsRequestCampaign",
        "paramsSyncLead" => "paramsSyncLead",
        "successGetCampaignsForSource" => "successGetCampaignsForSource",
        "successGetLead" => "successGetLead",
        "successGetLeadActivity" => "successGetLeadActivity",
        "successGetLeadChanges" => "successGetLeadChanges",
        "successListOperation" => "successListOperation",
        "successRequestCampaign" => "successRequestCampaign",
        "successSyncLead" => "successSyncLead");
}
