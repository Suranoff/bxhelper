<?
namespace Firestorm\BXHelper;
\CModule::IncludeModule('iblock');

class BXHelper {

    public static $obCache = null;
    const CACHE_TIME = 86700;
    const CACHE_PATH = '/BXHelper/';

    const COLLECTION_TAG_AND = 1;
    const FILE_TAG_AND = 2;
    const COMPLEX_TAG_AND = 4;
    /*private static $cache_id;

    private static $cached_vars = array();*/


    public static function Init () {
        static::$obCache = new \CPHPCache();
        if (!empty($_GET['clear_cache'])) {
            BXClearCache('/BXHelper/');
        }
    }
    public static function clearArray($arr, $only_full_null = false, $reset_keys = false) {
        $nulled_count = 0;
        if (is_array($arr)) {
            foreach ($arr as $k => $a) {
                if (empty($a)) {
                    if ($only_full_null) {
                        $nulled_count++;
                    } else {
                        unset($arr[$k]);
                    }
                }
            }
        }
        if ($only_full_null && $nulled_count == count($arr)) {
            return false;
        } else if (count($arr)) {
            if ($reset_keys) return array_values($arr);
            else return $arr;
        }
        return false;
    }
    public static function getIBlockId ($code, $site_id, $use_cache = true) {
        $iblock_list = null;
        $arIblock = null;
        $result = array();
        if ($use_cache) $iblock_list = static::getCache(__FUNCTION__.'_list');
        if (!is_array($iblock_list['RESULT'])) {
            \CModule::IncludeModule('iblock');
            $dbResult = \CIBlock::GetList();
            while ($next = $dbResult->fetch()) {
                $iblock_list['RESULT'][] = $next;
            }
            static::setCache(__FUNCTION__.'_list', $iblock_list['RESULT']);
        }
        if ($use_cache) $arIblock = static::getCache(__FUNCTION__.'_iblock');
        if (!is_array($arIblock['RESULT'])) {
            \CModule::IncludeModule('iblock');
            foreach ($iblock_list['RESULT'] as $iblock) {
                if ($iblock['CODE'] == $code) {
                    $dbResult = \CIBlock::GetSite($iblock['ID']);
                    while ($next = $dbResult->getNext()) {
                        if ($next['SITE_ID'] == $site_id) {
                            $arIblock['RESULT'][] = $iblock;
                        }
                    }
                }
            }
            static::setCache(__FUNCTION__.'_iblock', $arIblock['RESULT']);
        }
        if (is_array($arIblock['RESULT']) && count($arIblock['RESULT']) > 0) {
            foreach ($arIblock['RESULT'] as $iblock) {
                $result[] = $iblock['ID'];
            }
        }
        return $result;
    }
    public static function logout() {
        global $APPLICATION;
        return $APPLICATION->GetCurPageParam("logout=yes");
    }
    public static function getCache ($code) {

        $cache_id = md5('BXHelper'.$code.$_SERVER['HTTP_HOST']);
        //id кеша - зависит от имени функции $code и домена где было вызвано - для многосайтовости.

        if (is_object(static::$obCache) && static::CACHE_TIME > 0 && static::$obCache->InitCache(static::CACHE_TIME, $cache_id, static::CACHE_PATH))
        {
            $vars = static::$obCache->GetVars();
            if (!empty($vars['RESULT'])) {
                return $vars;
            } else {
                return false;
            }
        }
        return false;
    }
    public static function setCache ($code, $vars) {

        $cache_id = md5('BXHelper'.$code.$_SERVER['HTTP_HOST']);

        if (is_object(static::$obCache)) {
            static::$obCache->StartDataCache(static::CACHE_TIME, $cache_id, static::CACHE_PATH);
            static::$obCache->EndDataCache(array("RESULT"=>$vars));
        }
    }
    public static function addCachedKeys(&$component, $keys, $ar_result) {
        if (is_object($component) && count($ar_result) > 0 && is_array($ar_result)) {
            foreach ($keys as $val) {
                $component->arResult[$val] = $ar_result[$val];
            }
            $component->SetResultCacheKeys($keys);
        }
    }
    public static function ajax ($var) {
        print(str_replace("'","\"",\CUtil::PhpToJSObject($var)));
    }
    public static function js_data($var) {
        return htmlspecialchars(json_encode($var));
    }

    public static function getEditArea () {

    }

    // TODO доделать до полной синхронизации
    /*public static function syncListPropToSection ($iblock_id, $prop_id) {
        if (intval($iblock_id) && intval($prop_id) > 0) {
            \CModule::IncudeModule('iblock');
            $arSectionVariants = array();
            $obProperty = new \CIBlockProperty;
            $dbResult = $obProperty->GetPropertyEnum($prop_id);
            $dbResult = \CIBlockSection::GetList(array(), array(''), false, array('ID','CODE', 'NAME', 'SORT'));
            while ($next = $dbResult->GetNext()) {
                $next['VALUE'] = $next['NAME'];
                $next['XML_ID'] = 'xml_section_'.$next['CODE'];

                unset($next['VALUE']);
                unset($next['CODE']);

                $arSectionVariants[] = $next;
            }

            $obProperty = new \CIBlockProperty;
            return $obProperty->UpdateEnum($prop_id, $arSectionVariants);
        }
        return false;
    }*/
    public static function syncSectionToListProp ($iblock_id, $prop_id) {
        if (intval($iblock_id) && intval($prop_id) > 0) {
            \CModule::IncludeModule('iblock');
            $arSectionVariants = array();
            $dbResult = \CIBlockSection::GetList(array(), array(''), false, array('ID','CODE', 'NAME', 'SORT'));
            while ($next = $dbResult->GetNext()) {
                if (!empty($next['NAME']) && !empty($next['CODE'])) {
                    $next['VALUE'] = $next['NAME'];
                    $next['XML_ID'] = 'xml_section_'.$next['CODE'];

                    unset($next['VALUE']);
                    unset($next['CODE']);

                    $arSectionVariants[] = $next;
                }
            }

            $obProperty = new \CIBlockProperty;
            return $obProperty->UpdateEnum($prop_id, $arSectionVariants);
        }
        return false;
    }
    public static function declension($digit,$expr,$onlyword=true) //склонение слов
    {
        if(!is_array($expr)) $expr = array_filter(explode(' ', $expr));
        if(empty($expr[2])) $expr[2]=$expr[1];
        $i=preg_replace('/[^0-9]+/s','',$digit)%100;
        if($onlyword) $digit='';
        if($i>=5 && $i<=20) $res=$digit.' '.$expr[2];
        else
        {
            $i%=10;
            if($i==1) $res=$digit.' '.$expr[0];
            elseif($i>=2 && $i<=4) $res=$digit.' '.$expr[1];
            else $res=$digit.' '.$expr[2];
        }
        return trim($res);
    }
    public static function getPropertyByID ($id, $field_as_key = 'ID') {
        \CModule::IncludeModule('iblock');
        $dbResult = \CIBlockPirogovProperty::GetList(array(), array('ID' => $id));
        while ($next = $dbResult->GetNext()) {
            $ar_property[$next[$field_as_key]] = $next;
        }
        return $ar_property;
    }
    public static function getProperties($arOrder, $arFilter, $arSelect, $field_as_key = 'ID', $use_cache = true) {
        global $DB;
        $param_string = serialize(func_get_args());
        if ($use_cache) $result = static::getCache(__FUNCTION__.$param_string.'_prop');
        if (!is_array($result['RESULT'])) {
            $table_name = "b_iblock_property";
            $short_name = "ibp";
            $sql = "SELECT";
            if (empty($arSelect) || !is_array($arSelect)) {
                $select = ' * ';
            } else {
                $select = " ".implode(",",$arSelect)." ";
            }
            $sql .= $select."FROM ".$table_name." AS ".$short_name." ";
            //pr(array($arOrder, $arFilter));
            $where_array = array();
            $where = "WHERE ";
            foreach ($arFilter as $code => $value) {
                $symbol = "";
                $code = $short_name.".".$code;
                if (is_array($value)) {
                    array_walk($value,'BXHelper::string_for_sql');
                    $value = "(".implode(",",$value).")";
                    $symbol = " IN ";
                } else if (is_scalar($value)) {
                    if (!is_numeric($value)) {
                        static::string_for_sql($value);
                    }
                    $symbol = " = ";
                }
                if (strlen($symbol)) $where_array[] = $code.$symbol.$value;
            }

            $sql = $sql.$where.implode(" AND ", $where_array);

            $dbResult = $DB->Query($sql);
            while ($next = $dbResult->GetNext()) {
                $result['RESULT'][$next[$field_as_key]] = $next;
            }
            static::setCache(__FUNCTION__.$param_string.'_prop', $result['RESULT']);
        }
        return $result;
    }
    public static function getElementImagesPath (&$arItem, $codes = array('DETAIL_PICTURE', 'PREVIEW_PICTURE', 'PICTURE'), $userf_codes = array(), $resize = array(250,250)) {
        foreach ($codes as $code) {
            if (intval($arItem[$code])) {
                $arItem[$code] = array('ID' => $arItem[$code], 'SRC' => CFile::GetPath($arItem[$code]));
            }
        }
        foreach ($userf_codes as $uf_code) {
            if (is_array($arItem[$uf_code]) && count($arItem[$uf_code]) > 0) {
                foreach ($arItem[$uf_code] as &$img) {
                    $arFile = \CFile::ResizeImageGet($img, array('width' => $resize[0], 'height' => $resize[1]), BX_RESIZE_IMAGE_PROPORTIONAL_ALT);
                    $img = array('ID' => $img, 'SRC' => $arFile['src']);
                }
            } else if (intval($arItem[$uf_code])) {
                $arFile = \CFile::ResizeImageGet( $arItem[$uf_code], array('width' => $resize[0], 'height' => $resize[1]), BX_RESIZE_IMAGE_PROPORTIONAL_ALT);
                $arItem[$uf_code] = array('ID' => $arItem[$uf_code], 'SRC' => $arFile['src']);
            }
        }
    }
    public static function getElements($arOrder, $arFilter, $arGroup, $arNavigation, $arSelect, $use_cache = true, $field_as_key = false)
    {
        $param_string = serialize(func_get_args());
        if ($use_cache) $result = static::getCache(__FUNCTION__.$param_string.'_list');
        if (!is_array($result['RESULT'])) {
            \CModule::IncludeModule('iblock');
            $dbResult = \CIBlockElement::GetList($arOrder, $arFilter, $arGroup, $arNavigation, $arSelect);
            if (preg_grep ('/^PROPERTY_/', $arSelect)) {
                while ($next = $dbResult->GetNext()) {
                    if (!$field_as_key) {
                        $result['RESULT'][] = $next;
                    } else {
                        $nkey = $next[$field_as_key];
                        if (!empty($result['RESULT'][$nkey])) {
                            if (empty($result['RESULT'][$nkey][0])) {
                                $result['RESULT'][$nkey]= array($result['RESULT'][$nkey]);
                            }
                            $result['RESULT'][$nkey][] = $next;
                        } else {
                            $result['RESULT'][$nkey] = $next;
                        }
                    }
                }
            } else {
                while ($next = $dbResult->GetNextElement()) {
                    $properties = $next->GetProperties();
                    $fields = $next->GetFields();
                    if (!$field_as_key) {
                        $result['RESULT'][] = array_merge($fields, array('PROPERTIES' => $properties));
                    } else {
                        $fkey = $fields[$field_as_key];
                        $pkey = $properties[$field_as_key]['VALUE'];
                        if (isset($fkey)) {
                            if (!empty($result['RESULT'][$fkey])) {
                                if (empty($result['RESULT'][$fkey][0])) {
                                    $result['RESULT'][$fkey]= array($result['RESULT'][$fkey]);
                                }
                                $result['RESULT'][$fkey] = array_merge($fields, array('PROPERTIES' => $properties));

                            } else {
                                $result['RESULT'][$fkey] = array_merge($fields, array('PROPERTIES' => $properties));
                            }
                        } else if (isset($pkey)) {
                            if (!empty($result['RESULT'][$pkey])) {
                                if (empty($result['RESULT'][$pkey][0])) {
                                    $result['RESULT'][$pkey]= array($result['RESULT'][$pkey]);
                                }
                                $result['RESULT'][$pkey][] = array_merge($fields, array('PROPERTIES' => $properties));
                            } else {
                                $result['RESULT'][$pkey] = array_merge($fields, array('PROPERTIES' => $properties));
                            }
                        }
                    }
                }
            }
            static::setCache(__FUNCTION__.$param_string.'_list', $result['RESULT']);
        }
        return $result;
    }
    public static function getSites($arFilter = array(), $arOrder = array('SORT' => 'ASC'), $use_cache = true) {
        $param_string = serialize(func_get_args());
        if ($use_cache) $result = static::getCache(__FUNCTION__.$param_string.'_site');
        if (!is_array($result['RESULT'])) {
            $dbResult = \CSite::GetList($arOrder[0], $arOrder[1], $arFilter);
            while ($next = $dbResult->GetNext()) {
                $result['RESULT'][] = $next;
            }
            static::setCache(__FUNCTION__.$param_string.'_site', $result['RESULT']);
        }
        return $result;
    }

    public static function getNewPropValIndex ($arPropVal) {
//        $elem = end($arPropVal);
//        if (empty($elem['VALUE'])) {
//            if (preg_match("/^n/",key($arPropVal))) {
//
//            }
//        }
    }

    public static function  propValNotEmpty($prop_val) {
        $cur_val = current($prop_val);
        return !empty($cur_val["VALUE"]);
    }

    public static function array_true($arr, $and = true) {
        $arr_f = array_filter($arr);
        if ($and) {
            return count($arr_f) === count($arr);
        } else {
            return count($arr_f) > 0;
        }
    }

    public static function getResizedPictureByName($file_name, $resize = array('width' => 200, 'height' => 200), $resize_type) {
        $file_id = static::getFileByName($file_name);
        return static::getResizedPictureByID($file_id['ID'],$resize,$resize_type);
    }

    public static function getResizedPictureByID($file_id, $resize = array('width' => 200, 'height' => 200), $resize_type) {
        if (intval($file_id)) {
            $obFile = new \CFile();
            $arFile = $obFile->ResizeImageGet($file_id, $resize, $resize_type);
            return $arFile["src"];
        }
        return false;
    }

    public static function getAllLanguages() {
        $arSites = static::getSites();
        $site_lids = array();
        while ($site = array_shift($arSites['RESULT'])) {
            $site_lids[] = $site['LANGUAGE_ID'];
        }
        return $site_lids;
    }

    public static function getSimpleLanguageProperties ($property_codes, $languages = array(), $iblock_id) {
        $lang_codes = array();
        if (!count($languages)) {
            $languages = static::getAllLanguages();
        }
        foreach ($property_codes as $code) {
            foreach ($languages as $lang) {
                $lang_codes[] = $code."_".$lang;
            }
        }
        //pr($lang_codes);
        $arProperty_codes = array();
        $arProperties = static::getProperties(array(), array('IBLOCK_ID' => $iblock_id),'ID',false);
        foreach ($arProperties['RESULT'] as $arProp) {
            if (in_array($arProp['CODE'],$lang_codes)) {
                $arProperty_codes[] = $arProp['CODE'];
            }
        }
        return $arProperty_codes;
    }

    public static function isOrderExist ($id) {
        global $DB;
        $result = $DB->Query("SELECT COUNT(ID) as CNT FROM `b_sale_order` WHERE ID = $id")->GetNext();
        return intval($result['CNT']) > 0;
    }

    public static function unique_digit () {
        $unique_string = uniqid();
        echo $unique_string;
        $value = pack('H*', $unique_string);
        echo base_convert($value, 10, 16);
        return $value;
    }

    function convert_number_to_words($number) {

        $hyphen      = '-';
        $conjunction = ' and ';
        $separator   = ', ';
        $negative    = 'negative ';
        $decimal     = ' point ';
        $dictionary  = array(
            0                   => 'zero',
            1                   => 'one',
            2                   => 'two',
            3                   => 'three',
            4                   => 'four',
            5                   => 'five',
            6                   => 'six',
            7                   => 'seven',
            8                   => 'eight',
            9                   => 'nine',
            10                  => 'ten',
            11                  => 'eleven',
            12                  => 'twelve',
            13                  => 'thirteen',
            14                  => 'fourteen',
            15                  => 'fifteen',
            16                  => 'sixteen',
            17                  => 'seventeen',
            18                  => 'eighteen',
            19                  => 'nineteen',
            20                  => 'twenty',
            30                  => 'thirty',
            40                  => 'fourty',
            50                  => 'fifty',
            60                  => 'sixty',
            70                  => 'seventy',
            80                  => 'eighty',
            90                  => 'ninety',
            100                 => 'hundred',
            1000                => 'thousand',
            1000000             => 'million',
            1000000000          => 'billion',
            1000000000000       => 'trillion',
            1000000000000000    => 'quadrillion',
            1000000000000000000 => 'quintillion'
        );

        if (!is_numeric($number)) {
            return false;
        }

        if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
            // overflow
            trigger_error(
                'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
                E_USER_WARNING
            );
            return false;
        }

        if ($number < 0) {
            return $negative . convert_number_to_words(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens   = ((int) ($number / 10)) * 10;
                $units  = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds  = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . convert_number_to_words($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= convert_number_to_words($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = array();
            foreach (str_split((string) $fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }

        return $string;
    }


    public static function getSections($arOrder, $arFilter, $arNavigation, $arSelect, $use_cache=true, $as_key = false)
    {
        $param_string = serialize(func_get_args());
        if ($use_cache) $result = static::getCache(__FUNCTION__.$param_string.'_section');
        if (!is_array($result['RESULT'])) {
            \CModule::IncludeModule('iblock');
            $dbResult = \CIBlockSection::GetList($arOrder, $arFilter, $arNavigation, $arSelect);
            while ($next = $dbResult->GetNext()) {
                if ($as_key) {
                    $result['RESULT'][$next[$as_key]] = $next;
                } else {
                    $result['RESULT'][] = $next;
                }
            }
            static::setCache(__FUNCTION__.$param_string.'_section', $result['RESULT']);
        }
        return $result;
    }
    public static function  getFormResults($form_id, $arOrder, $selectAnswerFields ,$arFilter, $use_cache=true) {
        $param_string = serialize(func_get_args());

        if (!is_array($arOrder) || !count($arOrder)) {
            $arOrder = array('ID' => 'ASC');
        }
        $by = key($arOrder);
        $order = current($arOrder);
        $is_filtered = true;

        if ($use_cache) $result = static::getCache(__FUNCTION__.$param_string.'_form_result');
        if (!is_array($result['RESULT'])) {
            \CModule::IncludeModule('form');
            $dbResult = \CFormResult::GetList($form_id, $by, $order, $arFilter, $is_filtered);
            static::setCache(__FUNCTION__.$param_string.'_section', $result['RESULT']);
            while ($next = $dbResult->GetNext()) {
                \CFormResult::GetDataByID($next['ID'], $selectAnswerFields, $arResultFields, $arAnswers);
                $next = array_merge($next, $arResultFields);
                $next['ANSWERS'] = $arAnswers;
                $result['RESULT'][] = $next;
            }
            static::setCache(__FUNCTION__.$param_string.'_form_result', $result['RESULT']);
        }
        return $result;
    }

    public static function getUserDisplayName($user_id, $arUsers = array()) {
        if (empty($arUsers)) {
            $arUsers = static::getUserVariants($user_id);
        }
        if (!empty($arUsers[$user_id]['NAME']) && !empty($arUsers[$user_id]['LAST_NAME'])) {
            $display_name = $arUsers[$user_id]['NAME']." ".$arUsers[$user_id]['LAST_NAME'];
        } else {
            $display_name = $arUsers[$user_id]['LOGIN'];
        }
        return $display_name;
    }

    public static function buildCodeLinkPath ($iblock_id, $code_array) {
        $param_string = serialize(func_get_args());
        $result = static::getCache(__FUNCTION__.$param_string.'_codelink');

        if ( strval($result['RESULT']) <= 0 && \CModule::IncludeModule('iblock')) {
            $result['RESULT']['EXIST'] = 1;
            $size = count($code_array);
            $element_linked_array = array();
            if ($size > 1) {
                for ($key = 0; $key < $size; $key++) {
                    $filter = array("IBLOCK_ID" => $iblock_id, "CODE" => $code_array[$key], "ACTIVE" => "Y");
                    if ($key != $size-1) {
                        $filter["PROPERTY_CHILD_ELEMENTS.CODE"] = array($code_array[$key+1]);
                    }
                    $dbResult = \CIBlockElement::GetList(
                        array(),
                        $filter,
                        false, array('nTopCount' => 1), array('ID','NAME','CODE')
                    );
                    $cnt = $dbResult->SelectedRowsCount();
                    if (!intval($cnt)) {
                        $result['RESULT']['EXIST'] = 0;
                        break;
                    } else {
                        $element_linked_array[] = $dbResult->GetNext();
                    }
                }
            } else {
                $dbResult = \CIBlockElement::GetList(
                    array(),
                    array(
                        "IBLOCK_ID" => $iblock_id,
                        "CODE" => $code_array[0],
                        "ACTIVE" => "Y",
                    ),
                    false, false, array('ID','NAME','DETAIL_PAGE_URL')
                );
                $cnt = $dbResult->SelectedRowsCount();
                if (!intval($cnt)) {
                    $result['RESULT']['EXIST'] = 0;
                } else {
                    $element_linked_array[] = $dbResult->GetNext();
                }
            }
            $result['RESULT']['ELEMENT_LINKED'] = $element_linked_array;
            static::setCache(__FUNCTION__.$param_string.'_check', $result['RESULT']);

        } else if (!\CModule::IncludeModule('iblock')) {
            return false;
        }
        return $result['RESULT'];
    }
    public static function checkCodeLinkPath ($iblock_id, $code_array) {
        $result = static::buildCodeLinkPath($iblock_id, $code_array);
        return $result;
    }

    public static function addElementsToNavString ($root, $arElements) {
        $url = "/".$root."/";
        foreach ($arElements as $arElem) {
            $url .= $arElem['CODE']."/";
            if (!is_dir(abs_path($url))) {
                /*для того чтобы не дублировались ссылки которые попадают в цепочку навигации посредством стандартного Битрового цикла по вложенным директориям*/
                $GLOBALS['APPLICATION']->AddChainItem($arElem['NAME'], $url);
            }
        }
    }

    public static function TreeMenuToBitrix ($menu_raw, $depth, &$menu_array, $folder, $max_depth) {
        if (!is_array($menu_array)) {
            $menu_array = array();
        }
        if (!intval($depth)) {
            $depth = 1;
        }
        foreach ($menu_raw as $menu_link) {
            $is_parent = intval(!empty($menu_link['CHILDREN']) && $max_depth > $depth);
            $menu_array[] = array(
                $menu_link['NAME'],
                $folder."/".$menu_link['CODE']."/",
                array('/about_company/'),
                array('FROM_IBLOCK' => '1', 'DEPTH_LEVEL' => $depth, 'IS_PARENT' => $is_parent, 'CODE' => $menu_link['ELEMENT_CODE'])
            );
            if ( $is_parent) {
                $next_depth = $depth + 1;
                static::TreeMenuToBitrix($menu_link['CHILDREN'], $next_depth, $menu_array, $folder, $max_depth);
            }
        }
    }

    /*public static function translitIblockCodes() {
        $result = BXHelper::getElements(array(), array('IBLOCK_ID' => '7'), false, false, array(), false);
        $el = new \CIBlockElement();
        foreach ($result['RESULT'] as $res) {
            if (empty($res['CODE'])) {
                $el->Update($res['ID'], array('CODE' => $res['NAME']));
            }
        }

    }*/
    public static function getRecursiveLinks(&$links, &$indexes, $use_sort) {

        $menu_links = array();
        foreach ($links as $k => $l) {
            $children = array_flip($l['CHILDREN']);
            if (!is_array($indexes[$l['ID']])) {
                $menu_links[$l['ID']] = array('ID' => $l['ID'], 'NAME' => $l['NAME'], 'CHILDREN' => $children, 'CODE' => $l['CODE'], 'ELEMENT_CODE' => $l['CODE']);
                if ($use_sort) {
                    $menu_links[$l['ID']]['SORT'] = $l['SORT'];
                }
                $indexes[$l['ID']] = $menu_links[$l['ID']];
                foreach ($menu_links[$l['ID']]['CHILDREN'] as $key => &$child) {
                    if (!is_array($indexes[$key])) {
                        $child = array();
                        $child['CODE'] = $menu_links[$l['ID']]['CODE'];
                        $indexes[$key] = &$child;
                    } else if (is_array($menu_links[$key])) {
                        $child = $menu_links[$key];
                        $child['CODE'] = $indexes[$l['ID']]['CODE']."/".$child['CODE'];
                        $indexes[$key] = &$child;
                        unset($menu_links[$key]);
                    }
                }
            } else {
                $indexes[$l['ID']] = array('ID' => $l['ID'], 'CHILDREN' => $children, 'CODE' => $indexes[$l['ID']]['CODE']."/".$l['CODE'], 'NAME' => $l['NAME'], 'ELEMENT_CODE' => $l['CODE']);
                if ($use_sort) {
                    $indexes[$l['ID']]['SORT'] = $l['SORT'];
                }
                foreach ($indexes[$l['ID']]['CHILDREN'] as $key => &$child) {
                    if (!is_array($indexes[$key])) {
                        $child = array();
                        $child['CODE'] = $indexes[$l['ID']]['CODE'];
                        $indexes[$key] = &$child;
                    } else if (is_array($menu_links[$key])) {
                        $child = $menu_links[$key];
                        $child['CODE'] = $indexes[$l['ID']]['CODE']."/".$child['CODE'];
                        $indexes[$key] = &$child;
                        unset($menu_links[$key]);
                    }
                }
            }
        }

        if ($use_sort) {
            static::recursiveMenuSort($menu_links);

        }
        return $menu_links;
    }

    public static function recursiveMenuSort(&$menu) {
        foreach ($menu as &$menu_entry) {
            if (count($menu_entry['CHILDREN']) > 0) {
                static::complex_sort($menu_entry['CHILDREN'], array('SORT'));
                static::recursiveMenuSort($menu_entry['CHILDREN']);
            }
        }
    }

    public static function GetExt($src) {
        $mime_type = mime_content_type(abs_path($src));/*надо*/
        //echo $mime_type;
        if(strpos($mime_type, "pdf") != false) {
            $fileExt  = "files__pdf";
            $fileType = "Adobe Acrobat";
        } else if (strpos($mime_type, "word") != false || preg_match("/\.doc.*$/i", $src)) {
            $fileExt  = "files__doc";
            $fileType = "Microsoft Word";
        } else if (strpos($mime_type, "excel") != false || strpos($mime_type, "sheet") != false || preg_match("/\.xls.$/i", $src)) {
            $fileExt  = "files__xls";
            $fileType = "Microsoft Excel";
        } else if (strpos($mime_type, "jpeg") != false || strpos($mime_type, "jpg") != false  || preg_match("/(\.jpg$|\.jpeg$)/i", $src)) {
            $fileExt  = "files__jpg";
            $fileType = "JPG";
        }
        return array($fileExt, $fileType);
    }

    public static function NotFound () {
        header("HTTP/1.1 404 Not Found");
        define("ERROR_404","Y");
    }
    public static function aggregateTags($arFilter) {
        $tags_result = static::getElements(array('SORT' => 'ASC'), $arFilter, false, false, array('ID', 'NAME', 'TAGS'));
        $tags = array();
        $tag_string = "";
        if (!empty($tags_result['RESULT'])) {
            foreach ($tags_result['RESULT'] as $tag_element) {
                if (!empty($tag_element['TAGS'])) {
                    $cur_tags = explode(",",$tag_element['TAGS']);
                    foreach ($cur_tags as $tag) {
                        if (!isset($tags[$tag])) {
                            $tags[$tag] = trim($tag);
                        }
                    }
                }
            }
            $tag_string = implode(",",array_values($tags));
        }
        return $tag_string;
    }
    public static function getEnum($prop_id, $arOrder, $arFilter, $field_as_key = 'ID', $use_cache = true) {
        $param_string = serialize(func_get_args());
        if ($use_cache) {
            $result = static::getCache(__FUNCTION__.$param_string.'_prop_enum');
        }
        if (empty($result['RESULT'])) {
            $arProperties = array();
            $dbResult = \CIBlockProperty::GetPropertyEnum($prop_id, $arOrder, $arFilter);
            while ($next = $dbResult->GetNext()) {
                $arProperties[$next[$field_as_key]] = $next;
            }
            $result['RESULT'] = $arProperties;
            static::setCache(__FUNCTION__.$param_string.'_prop', $result['RESULT']);
        }
        return $result['RESULT'];

    }

    public static function getFileByName($file_name, $use_cache = true) {
        $param_string = serialize(func_get_args());
        if ($use_cache) {
            $result = static::getCache(__FUNCTION__.$param_string.'_file');
        }
        if (empty($result['RESULT'])) {
            $file_name = basename($file_name);
            $obFile = new \CFile();
            $arFile = $obFile->GetList(array(), array("FILE_NAME" => $file_name))->GetNext();
            $result['RESULT'] = $arFile;
            static::setCache(__FUNCTION__.$param_string.'_file', $result['RESULT']);
        }
        return $result['RESULT'];
    }

    public static function getFileByHash($hash, $use_cache = true) {
        $param_string = serialize(func_get_args());
        if ($use_cache) {
            $result = static::getCache(__FUNCTION__.$param_string.'_file');
        }
        if (empty($result['RESULT'])) {
            $obFile = new \CFile();
            $arFile = $obFile->GetList(array(), array("EXTERNAL_ID" => $hash))->GetNext();
            $result['RESULT'] = $arFile;
            static::setCache(__FUNCTION__.$param_string.'_file', $result['RESULT']);
        }
        return $result['RESULT'];
    }

    public static function getPropertyEnum($arOrder, $arFilter, $field_as_key = 'ID', $use_cache = true) {
        $param_string = serialize(func_get_args());
        if ($use_cache) $result = static::getCache(__FUNCTION__.$param_string.'_prop_enum');
        if (empty($result['RESULT'])) {
            $arProperties = array();
            $dbResult = \CIBlockPropertyEnum::GetList($arOrder, $arFilter);
            while ($next = $dbResult->GetNext()) {
                $arProperties[$next[$field_as_key]] = $next;
            }
            $result['RESULT'] = $arProperties;
            static::setCache(__FUNCTION__.$param_string.'_prop_enum', $result['RESULT']);
        }
        return $result['RESULT'];
    }

    public static function getDirectoryVariants($code,$iblock_id, $as_result_key = false) {//тип "Справочник" в Битриксе определяется кодом "directory"
        if (intval($code) > 0) {
            $as_key = "ID";
        } else {
            $as_key = "CODE";
        }
        $propFilter = array($as_key => $code);
        if (intval($iblock_id)) $propFilter['IBLOCK_ID'] = $iblock_id;

        $properties = static::getProperties(array(), $propFilter,array(),$as_key, false);
        $settings = unserialize($properties['RESULT'][$code]['~USER_TYPE_SETTINGS']);
        $table_name = $settings['TABLE_NAME'];
        /** @var $dbResult  CDBResult*/
        $dbResult = static::getListByTableNameHighload($table_name, array('ID','UF_XML_ID'));
        $result =  array();

        while ($next = $dbResult->fetch()) {
            if ($as_result_key) {
                $result[$next[$as_result_key]] = $next;
            } else {
                $result[] = $next;
            }
        }
        return $result;
    }

    public static function addDirectoryVariant($code,$iblock_id,$values, $use_cache = false) {
        if (intval($code) > 0) {
            $as_key = "ID";
        } else {
            $as_key = "CODE";
        }

        $propFilter = array($as_key => $code);
        if (intval($iblock_id)) $propFilter['IBLOCK_ID'] = $iblock_id;

        $properties = static::getProperties(array(), $propFilter,array(),$as_key, $use_cache);
        $settings = unserialize($properties['RESULT'][$code]['~USER_TYPE_SETTINGS']);
        $table_name = $settings['TABLE_NAME'];

        return static::addEntryByTableNameHighload($table_name,$values);
    }

    public static function addEntryByTableNameHighload ($table_name, $field_values) {
        global $DB;
        $hl_result = $DB->Query("SELECT * FROM b_hlblock_entity WHERE TABLE_NAME='$table_name'")->getNext();
        if (intval($hl_result['ID'])) {
            $hlblock   = \Bitrix\Highloadblock\HighloadBlockTable::getById( $hl_result['ID'] )->fetch();
            $entity   = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity( $hlblock );

            $entity_data_class = $entity->getDataClass();
            return $entity_data_class::add($field_values);
        }
        return false;
    }

    public static function getListByTableNameHighload ($table_name, $arSelect) {
        global $DB;
        $hl_result = $DB->Query("SELECT * FROM b_hlblock_entity WHERE TABLE_NAME='$table_name'")->getNext();
        if (intval($hl_result['ID'])) {
            $hlblock   = \Bitrix\Highloadblock\HighloadBlockTable::getById( $hl_result['ID'] )->fetch();
            $entity   = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity( $hlblock );

            $entity_data_class = $entity->getDataClass();
            return $entity_data_class::getList(array('select' => $arSelect));
        }
        return false;
    }

    public static function getArray($var){
        if (is_scalar($var) && !empty($var)) {
            return array($var);
        } else {
            return $var;
        }
    }
    public static function getUserVariants($arID, $arParams = array(), $use_cache = true) {
        $param_string = serialize(func_get_args());
        if ($use_cache) {
            $result = static::getCache(__FUNCTION__.$param_string.'_prop_user_variants');
        }
        if (empty($result)) {
            $arResult = array();
            $arFilter = array();
            if (!empty($arID)) {
                $ID = implode(" | ", $arID);
                $arFilter['ID'] = $ID;
            }
            $by = "id";
            $order = "asc";
            $obUser = new \CUser();
            $dbResult = $obUser->GetList($by, $order, $arFilter, $arParams);
            while ($next = $dbResult->GetNext()) {
                $arResult[$next['ID']] = $next;
            }
            $result['RESULT'] = $arResult;
            static::setCache(__FUNCTION__.$param_string.'_prop_user_variants', $result['RESULT']);
        }
        return $result['RESULT'];
    }
    public static function ajax_buffer_start() {
        if (isAjax()) {
            ob_start();
        }
    }
    public static function ajax_buffer_release ($condition = false) {
        if (isAjax())
            if ($condition) {
                ob_end_flush();
            } else {
                ob_end_clean();
            }
    }
    public static function ajax_buffer_toggle ($condition = false) {
        static::ajax_buffer_release($condition);
        static::ajax_buffer_start();
    }

    public static function start_ajax_block () {
        global $APPLICATION;
        if (isAjax()) $APPLICATION->RestartBuffer();
        ob_start();
    }

    public static function end_ajax_block ($content_name, $context_sort, $get_element = false, $get_inner_html = false) {
        global $APPLICATION;
        $content = ob_get_clean();
        if (!isAjax()) {
            if ($content_name) {
                $APPLICATION->AddViewContent($content_name, $content ,$context_sort);
            } else {
                print($content);
            }
        } else {
            if ($get_element) {
                $content = static::utf8_to_entities($content);

                $obHtml = \Sunra\PhpSimple\HtmlDomParser::str_get_html( $content );

                foreach($obHtml->find($get_element) as $element) {
                    if ($get_inner_html) echo $element->innertext;
                    else echo $element->outertext;

                }
            } else if ($get_inner_html)  {
                $content = static::utf8_to_entities($content);
                $obHtml = \Sunra\PhpSimple\HtmlDomParser::str_get_html( $content );
                echo $obHtml->firstChild()->innertext;
            } else
                echo $content;
            exit();
        }
    }

    public static function getNokogiriElement($content) {

    }

    public static function  getClassInfo($obj_class) {
        if (is_string($obj_class)) {
            return array('methods' => get_class_methods($obj_class));
        } else if (is_object($obj_class)) {
            return array('class' => get_class($obj_class), 'methods' => get_class_methods($obj_class), 'vars' => get_object_vars($obj_class));
        }
        return false;
    }

    public static function  getXpathSubquery($expression){
        $query = '';
        if (preg_match("/(?P<tag>[a-z0-9]+)?(\[(?P<attr>\S+)=(?P<value>\S+)\])?(#(?P<id>\S+))?(\.(?P<class>\S+))?/ims", $expression, $subs)){
            $tag = $subs['tag'];
            $id = $subs['id'];
            $attr = $subs['attr'];
            $attrValue = $subs['value'];
            $class = $subs['class'];
            if (!strlen($tag))
                $tag = '*';
            $query = '//'.$tag;
            if (strlen($id)){
                $query .= "[@id='".$id."']";
            }
            if (strlen($attr)){
                $query .= "[@".$attr."='".$attrValue."']";
            }
            if (strlen($class)){
                //$query .= "[@class='".$class."']";
                $query .= '[contains(concat(" ", normalize-space(@class), " "), " '.$class.' ")]';
            }
        }
        return $query;
    }

    public static function buildUrl ($root, $query_array) {
        $url = false;
        if (!empty($root)) {
            $url .= $root;
        } else {
            return $url;
        }
        if (!empty($query_array)) {
            $url .= "?".urldecode(http_build_query($query_array));
        }
        return $url;
    }

    public static function startTime() {
        return $start = microtime(true);
    }

    public static function endTime ($start) {
        return $time_elapsed_secs = microtime(true) - $start;
    }

    public static function getStoreAdminLink($store_id) {
        return "/bitrix/admin/cat_store_edit.php?ID=".$store_id."&lang=".LANGUAGE_ID;
    }

    public static function is_email($var) {
        $r = filter_var($var, FILTER_VALIDATE_EMAIL);
        return !empty($r);
    }

    public static function getElementByFieldValue ($array, $key, $value, $get_field_value = false) {
        if (is_array($array)) {

            foreach ($array as $arr) {
                if ($arr[$key] == $value) {
                    if ($get_field_value) {
                        return $arr[$get_field_value];
                    } else {
                        return $arr;
                    }
                }
            }
        }
        return false;
    }

    public static function adjustBrightness($hex, $steps) {
        // Steps should be between -255 and 255. Negative = darker, positive = lighter
        $steps = max(-255, min(255, $steps));

        // Normalize into a six character long hex string
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
        }

        // Split into three parts: R, G and B
        $color_parts = str_split($hex, 2);
        $return = '#';

        foreach ($color_parts as $color) {
            $color   = hexdec($color); // Convert to decimal
            $color   = max(0,min(255,$color + $steps)); // Adjust color
            $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
        }

        return $return;
    }

    public static function complex_sort_v1 ($input_array, $keys) {
        $valid_keys = array();
        $temp = array();
        foreach ($input_array as $key => $ar) {
            if (!is_array($ar) || !count($ar)) {
                $temp[] = $input_array[$key];
                unset($input_array[$key]);
            }
        }
        foreach ($keys as $k) {
            $key_is_valid = true;
            foreach ($input_array as  $ar) {
                if (!array_key_exists($k, $ar)) {
                    $key_is_valid = false;
                    break;
                }
            }
            if ($key_is_valid) {
                $valid_keys[] = $k;
            }
        }
        if (!empty($valid_keys)) {
            $sort_arrays = array();
            foreach ($valid_keys as $n => $v_key) {
                foreach ($input_array as $arr) {
                    $sort_arrays[$n][] = $arr[$v_key];
                }
            }
            foreach ($sort_arrays as &$s_arr) {
                $function_param[] = &$s_arr;
            }
            $function_param[] = &$input_array;
            call_user_func_array('array_multisort', $function_param);
            $input_array = array_merge($input_array, $temp);
            return $input_array;
        }
        return false;
    }
    public static function complex_sort ($input_array, $keys, $exclude_element = true, $unset_sort_key = array()) {

        $valid_keys = array();
        $temp = array();
        foreach ($input_array as $key => $ar) {
            if (!is_array($ar) || !count($ar)) {
                $temp[] = $input_array[$key];
                unset($input_array[$key]);
            }
        }
        foreach ($keys as  $key => $sort) {
            $key_is_valid = true;
            if (!in_array($sort, array('DESC', 'ASC'), true)) {
                $key = $sort;
                $sort = 'ASC';
            };
            foreach ($input_array as  $ar) {
                if (!array_key_exists($key, $ar)) {
                    if ($exclude_element) {
                        $key_is_valid = false;
                        break;
                    } else {
                        $temp[] = $input_array[$key];
                        unset($input_array[$key]);
                    }
                }
            }
            if ($key_is_valid) {
                $valid_keys[$key] = $sort;
            }
        }

        if (!empty($valid_keys)) {
            $sort_arrays = array();
            foreach ($valid_keys as $n => $v_key) {
                foreach ($input_array as $arr) {
                    $sort_arrays[$n][] = $arr[$n];
                }
            }
            foreach ($sort_arrays as $key => &$s_arr) {
                $function_param[] = &$s_arr;
                $function_param[] = constant('SORT_'.$valid_keys[$key]);
            }
            $function_param[] = &$input_array;
            call_user_func_array('array_multisort', $function_param);
            $input_array = array_merge($input_array, $temp);
            foreach ($input_array as &$element) {
                foreach ($unset_sort_key as $ukey) {
                    unset($element[$ukey]);
                }
            }
            return $input_array;
        }
        return false;
    }
    function rus2translit($string) {
        $converter = array(
            'а' => 'a',   'б' => 'b',   'в' => 'v',
            'г' => 'g',   'д' => 'd',   'е' => 'e',
            'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
            'и' => 'i',   'й' => 'y',   'к' => 'k',
            'л' => 'l',   'м' => 'm',   'н' => 'n',
            'о' => 'o',   'п' => 'p',   'р' => 'r',
            'с' => 's',   'т' => 't',   'у' => 'u',
            'ф' => 'f',   'х' => 'h',   'ц' => 'c',
            'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
            'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',
            'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

            'А' => 'A',   'Б' => 'B',   'В' => 'V',
            'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
            'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
            'И' => 'I',   'Й' => 'Y',   'К' => 'K',
            'Л' => 'L',   'М' => 'M',   'Н' => 'N',
            'О' => 'O',   'П' => 'P',   'Р' => 'R',
            'С' => 'S',   'Т' => 'T',   'У' => 'U',
            'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
            'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
            'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',
            'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
        );
        return strtr($string, $converter);
    }
    function str2url($str) {
        // переводим в транслит
        $str = static::rus2translit($str);
        // в нижний регистр
        $str = strtolower($str);
        // заменям все ненужное нам на "-"
        $str = preg_replace('~[^-a-z0-9_]+~u', '-', $str);
        // удаляем начальные и конечные '-'
        $str = trim($str, "-");
        return $str;
    }
    function randStr($length = 10, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    public  static  function getExtensionByMimeType ($mime_type) {
        $mimetypes = false;
        if (file_exists(dirname(__FILE__)."/json/mimetypes.json")) {
            $filename = dirname(__FILE__)."/json/mimetypes.json";
        } else {
            $filename = "https://raw.githubusercontent.com/skyzyx/mimetypes/master/mimetypes.json";
        }
        $json = file_get_contents($filename);
        if ($json) {
            $mimetypes = array_flip(json_decode($json, true));
        }
        if (is_array($mimetypes) && isset($mimetypes[$mime_type])) {
            return $mimetypes[$mime_type];
        }
        return false;
    }
    public static function getDownloadLink ($id, $script_name = "download.php") {
        if (is_file($_SERVER['DOCUMENT_ROOT']."/".$script_name)) {
            return "/".$script_name."?id=".$id;
        }
        return false;
    }
    public static function getFileIDByHash ($hash) {
        if (!empty($hash) && !empty($rid)) {
            return "/bitrix/tools/form_show_file.php?$rid=196&hash=$hash&lang=".LANGUAGE_ID."&action=download";
        }
        return false;
    }

    public static function countPropertyValues($prop_values, $get_this_codes) {
        if (is_array($get_this_codes)) {
            $count = 0;
            foreach ($get_this_codes as $code) {
                $value = $prop_values[$code]['VALUE'];
                if (!empty($value)) {
                    if (is_array($value)) {
                        $count += count($value);
                    } else {
                        $count++;
                    }
                }
            }
            return $count;
        }
        return false;
    }

    public static function getMediaByTags($collection_tags, $file_tags, $flags ,$field_as_key = false ,$use_cache = true) {

        $param_string = serialize(func_get_args());
        if ($use_cache) {
            $result = static::getCache(__FUNCTION__.$param_string.'_media_tags');
        }

        if (empty($result['RESULT'])) {
            global $DB;
            if (is_scalar($collection_tags)) $collection_tags = array($collection_tags);
            if (is_scalar($file_tags)) $file_tags = array($file_tags);
            if ((is_array($collection_tags) && count($collection_tags))  || (is_array($file_tags) && count($file_tags))) {

                if ($flags & static::COLLECTION_TAG_AND) {
                    $coll_tag_logic = 'AND';
                } else {
                    $coll_tag_logic = 'OR';
                }

                if ($flags & static::FILE_TAG_AND) {
                    $file_tag_logic = 'AND';
                } else {
                    $file_tag_logic = 'OR';
                }

                if ($flags & static::COMPLEX_TAG_AND) {
                    $complex_tag_logic = 'AND';
                } else {
                    $complex_tag_logic = 'OR';
                }

                $mli = 'b_medialib_item';
                $mlc = 'b_medialib_collection';
                $mlci ='b_medialib_collection_item';

                $do_complex_where = false;

                $query = "SELECT $mli.*, $mlc.NAME as `COLLECTION_NAME`, $mlc.ID as `COLLECTION_ID` FROM $mli JOIN $mlci ON $mlci.ITEM_ID = $mli.ID JOIN $mlc ON $mlc.ID = $mlci.COLLECTION_ID";
                $where_string = " ";
                if (!empty($collection_tags)) {
                    $do_complex_where = true;
                    $where_arr = array();
                    foreach ($collection_tags as $tag) {
                        $where_arr[] = " $mlc.KEYWORDS LIKE '%$tag%' ";
                    }
                    $where_string .= implode($coll_tag_logic,$where_arr);
                }
                if (!empty($file_tags)) {
                    if ($do_complex_where) {
                        $where_string = " (".$where_string.") ";
                        $where_string .= $complex_tag_logic;
                    }
                    $where_arr = array();
                    foreach ($file_tags as $tag) {
                        $where_arr[] = " $mli.KEYWORDS LIKE '%$tag%' ";
                    }
                    if ($do_complex_where) {
                        $where_string .= " (".implode($file_tag_logic,$where_arr).") ";
                    } else {
                        $where_string .= implode($file_tag_logic,$where_arr);
                    }

                }
                $where_string = " WHERE".$where_string;
                $query .= $where_string;
                $dbResult = $DB->Query($query);
                while ($next = $dbResult->GetNext()) {
                    if (!$field_as_key) {
                        $result['RESULT'][] = $next;
                    } else {
                        $nkey = $next[$field_as_key];
                        if (!empty($result['RESULT'][$nkey])) {
                            if (empty($result['RESULT'][$nkey][0])) {
                                $result['RESULT'][$nkey]= array($result['RESULT'][$nkey]);
                            }
                            $result['RESULT'][$nkey][] = $next;
                        } else {
                            $result['RESULT'][$nkey] = $next;
                        }
                    }
                }

            }
            static::setCache(__FUNCTION__.$param_string.'_media_tags', $result['RESULT']);
        }

        return $result;
    }
    public static function format_bytes($bytes, $units = array('B', 'KB', 'MB', 'GB'))
    {
        //$bytes = sprintf('%u', filesize($path));

        if ($bytes > 0)
        {
            $unit = intval(log($bytes, 1024));

            if (array_key_exists($unit, $units) === true)
            {
                return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
            }
        }

        return $bytes;
    }

    public static function json_pretty_print($input_json) {
        $result      = '';
        $pos         = 0;
        $strLen      = strlen($input_json);
        $indentStr   = '    ';
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i=0; $i<=$strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($input_json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

                // If this character is the end of an element,
                // output a new line and indent the next line.
            } else if(($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;
    }

    public static function getBase64Mimetype($file64_string) {
        if (preg_match("/(?<=data:)(.+)(?=;base64)/", $file64_string, $matches)) {
            return $matches[0];
        }
        return false;
    }
    public static function utf8_to_entities($utf8string) {
        $convmap = array(0x80, 0xffff, 0, 0xffff);
        return mb_encode_numericentity($utf8string, $convmap, 'UTF-8');
    }

    function rand_color() {
        return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }

    public static function string_for_sql (&$value) {
        $value = "'".strval($value)."'";
    }
    public static function rewriteSingleUserPropertiesToSort (&$arFields, $property_codes) {

        $ar_raw_codes = array();
        $ar_sort_codes = array();

        foreach ($property_codes as $raw_code => $sort_code) {
            $ar_raw_codes[] = $raw_code;
            $ar_sort_codes[] = $sort_code;
        }
        $properties = static::getProperties(array(), array('IBLOCK_ID' => $arFields['IBLOCK_ID']),array('ID','CODE'), 'ID', false);

        foreach ($arFields['PROPERTY_VALUES'] as $key => $arPropVal) {
            $code = $properties['RESULT'][$key]['CODE'];
            $i = array_search($code, $ar_raw_codes);
            if ($i !== false) {
                $arUserVal = current($arPropVal);
                $user_id = $arUserVal['VALUE'];
                $userResult = static::getUserVariants(array($user_id), array(), false);
                $login = $userResult[$user_id]['LOGIN'];
                $user_name = $userResult[$user_id]['NAME']." ".$userResult[$user_id]['LAST_NAME'];
                $sort_value = $user_name !== "" ? $user_name : $login;
                foreach ($properties['RESULT'] as $sortProperty) {
                    if ($sortProperty['CODE'] == $ar_sort_codes[$i]) {
                        $sort_prop_val = current($arFields['PROPERTY_VALUES'][$sortProperty['ID']]);
                        $val_key = key($arFields['PROPERTY_VALUES'][$sortProperty['ID']]);
                        if (!empty($sort_prop_val['VALUE'])) {
                            unset($arFields['PROPERTY_VALUES'][$sortProperty['ID']][$val_key]);
                        }
                        $arFields['PROPERTY_VALUES'][$sortProperty['ID']]['n0']['VALUE'] = $sort_value;
                    }
                }
            }
        }
    }

    public static function rewriteSKUPropertiesToSort ($arFields, $property_codes) {

    }
    public static function daytosec($days) {
        return floatval($days)*86400;
    }

    public static function GetUserFields($arOrder, $arFilter = array(), $arSelect = array('*')) {
        global $DB;

        $uft = 'uft';
        $uft_long = 'b_user_field';

        $sql = "";

        if ((is_scalar($arFilter['USER_TYPE_ID']) && $arFilter['USER_TYPE_ID'] == 'enumeration') ||
            (is_array($arFilter['USER_TYPE_ID']) && in_array('enumeration', $arFilter['USER_TYPE_ID']))) {
            $ufe = 'ufe';
            $ufe_long = 'b_user_field_enum';
        }

        $arEnumSelect = array();
        $arLangSelect = array();
        $arSelectAliases = array();
        foreach ($arSelect as $key => $field) {
            if (is_string($key)) {
                $arSelectAliases[$field] = $key;
            }
            if (preg_match("/^ENUM_/",$field))  {
                unset($arSelect[$key]);
                $arEnumSelect[$key] = $field;
            } else if (preg_match("/^LANG_/",$field)) {
                unset($arSelect[$key]);
                $arLangSelect[$key] = $field;
            }
        }

        if (!empty($arEnumSelect)) {
            $ufe = 'ufe';
            $ufe_long = 'b_user_field_enum';
            $arEnumSelect = static::filterTableNames($ufe_long, $arEnumSelect, "ENUM_", true);
        }

        if (!empty($arLangSelect)) {
            $ufl = 'ufl';
            $ufl_long = 'b_user_field_lang';
            $arLangSelect = static::filterTableNames($ufl_long, $arLangSelect, "LANG_", true);
        }

        $arSelect = static::filterTableNames($uft_long, $arSelect);


        if (empty($arSelect)) {
            $arSelect = array("*");
        }
        $select = "SELECT ";
        $select_arr = array();

        foreach ($arSelect as $field) {
            if (isset($arSelectAliases[$field])) {
                $select_arr[] = $uft.".".$field." AS ".$arSelectAliases[$field];
            } else {
                $select_arr[] = $uft.".".$field;
            }
        }
        $select .= implode(", ",$select_arr);
        $select_arr = array();

        if (isset($ufe)) {
            $select .= ", ";
            foreach ($arEnumSelect as $field) {
                if (isset($arSelectAliases["ENUM_".$field])) {
                    $select_arr[] = $ufe.".".$field." AS ".$arSelectAliases["ENUM_".$field];
                } else {
                    $select_arr[] = $ufe.".".$field;
                }
            }
            $select .= implode(", ",$select_arr);
        }
        $select_arr = array();
        if (isset($ufl)) {
            $select .= ", ";
            foreach ($arLangSelect as $field) {
                if (isset($arSelectAliases["LANG_".$field])) {
                    $select_arr[] = $ufl.".".$field." AS ".$arSelectAliases["LANG_".$field];
                } else {
                    $select_arr[] = $ufl.".".$field;
                }
            }
            $select .= implode(", ",$select_arr);
        }
        $select .= " FROM ".$uft_long." AS ".$uft;


        $where = " WHERE ";
        $join = "";

        $arEnumFilter = array();
        $arLangFilter = array();


        $arWhereSymbols = array();

        foreach ($arFilter as $key => &$filter_value) {

            $valid_key = $key;

            if ($real_fname = array_search($valid_key,$arSelectAliases)) {
                $valid_key = $real_fname;
            }

            if (!empty($filter_value)) {
                if (is_scalar($filter_value)) {
                    if (is_string($filter_value)) static::string_for_sql($filter_value);
                    if (in_array($key[0], array("<>","<","<=",">",">="))) {
                        $s = $key[0];
                        $key = substr($key,1);

                        $arWhereSymbols[$valid_key] = false;
                        if (is_string($filter_value) && preg_match("/%{0,1}.+%{0,1}/u",$filter_value)) {
                            $key = "LIKE";
                        }
                    } else {
                        $s = "=";
                    }
                    $arWhereSymbols[$valid_key] = $s;
                } else if(is_array($filter_value)) {
                    $arWhereSymbols[$valid_key] = 'IN';
                    foreach ($filter_value as &$fval) {
                        if (is_string($fval)) static::string_for_sql($fval);
                    }
                    $filter_value = "(".implode(",",$filter_value).")";
                }
            }


            if (preg_match("/^ENUM_/",$valid_key))  {
                unset($arFilter[$key]);
                $arEnumFilter[$valid_key] = $filter_value;
            } else if (preg_match("/^LANG_/",$valid_key)) {
                unset($arFilter[$key]);
                $arLangFilter[$valid_key] = $filter_value;
            }
        }

        $arLangFilterKeys = array_flip($arLangFilter);
        $arEnumFilterKeys = array_flip($arEnumFilter);
        $arFilterKeys = array_flip($arFilter);

        if (!empty($arLangFilterKeys)) {
            $ufl = 'ufl';
            $ufl_long = 'b_user_field_lang';
            $arLangFilterKeys = static::filterTableNames($ufl_long, $arLangFilterKeys,"LANG_",true);
        }

        if (!empty($arEnumFilterKeys)) {
            $ufe = 'ufe';
            $ufe_long = 'b_user_field_enum';
            $arEnumFilterKeys = static::filterTableNames($ufe_long, $arEnumFilterKeys,"ENUM_",true);
        }

        if (!empty($arFilterKeys)) {
            $arFilterKeys = static::filterTableNames($uft_long, $arFilterKeys);
        }

        $ar_join = array();

        if ($ufe) {
            $ar_join[] = $ufe_long." AS ".$ufe." ON ".$uft.".ID = ".$ufe.".USER_FIELD_ID";
        }

        if ($ufl) {
            $ar_join[] = $ufl_long." AS ".$ufl." ON ".$uft.".ID = ".$ufl.".USER_FIELD_ID";
        }

        if (!empty($ar_join)) {
            $join = " LEFT JOIN ".implode(" LEFT JOIN ",$ar_join);
        }


        if (empty($arEnumFilterKeys) && empty($arLangFilterKeys) && empty($arFilterKeys)) {
            $where .= " 1";
        } else {
            $ar_where = array();
            foreach ($arEnumFilterKeys as $key) {
                $oldkey = "ENUM_".$key;
                if (isset($arEnumFilter[$oldkey]) && isset($arWhereSymbols[$oldkey])) {
                    $ar_where[] = $ufe.".".$key." ".$arWhereSymbols[$oldkey]." ".$arEnumFilter[$oldkey];
                }
            }
            foreach ($arLangFilterKeys as $key) {
                $oldkey = "LANG_".$key;
                if (isset($arLangFilter[$oldkey]) && isset($arWhereSymbols[$oldkey])) {
                    $ar_where[] = $ufl.".".$key." ".$arWhereSymbols[$oldkey]." ".$arLangFilter[$oldkey];
                }
            }
            foreach ($arFilterKeys as $key) {
                $oldkey = $key;
                if (isset($arFilter[$oldkey]) && isset($arWhereSymbols[$oldkey])) {
                    $ar_where[] = $uft.".".$key." ".$arWhereSymbols[$oldkey]." ".$arFilter[$oldkey];
                }
            }
            $where .= implode(" AND ",$ar_where);
        }

        $sql = $select.$join.$where." ORDER BY ".$uft.".SORT ASC";

        return $DB->Query($sql);
    }

    public static function buildAttributesHtml($attr_arr) {
        $attr_string = " ";
        foreach ($attr_arr as $name => $attr) {
            $attr_string .= $name."="."\"".$attr."\"";
        }
        $attr_string .= " ";
        return $attr_string;
    }

    public static function buildClassesHtml($class_arr) {
        return "class=\"".implode(" ",$class_arr)."\"";
    }

    public static function getBasketUserFilter()
    {
        \CModule::IncludeModule('sale');
        $fUserID = IntVal(\CSaleBasket::GetBasketUserID(True));
        return ($fUserID > 0)
            ? array("FUSER_ID" => $fUserID, "LID" => SITE_ID, "ORDER_ID" => "NULL")
            : null; // no basket for current user
    }

    public static function getBasketTotalPrice($currency)
    {
        \CModule::IncludeModule('sale');
        if (! ($userFilter = static::getBasketUserFilter()))
            return array();

        $rsBasket = \CSaleBasket::GetList(
            array(),
            $userFilter + array("CAN_BUY" => "Y", "DELAY" => "N", "SUBSCRIBE" => "N"),
            false,
            false,
            array(
                "QUANTITY", "PRICE", "CURRENCY", "DISCOUNT_PRICE", "WEIGHT", "VAT_RATE",
                "ID", "SET_PARENT_ID", "PRODUCT_ID", "CATALOG_XML_ID", "PRODUCT_XML_ID",
                "PRODUCT_PROVIDER_CLASS", "TYPE"
            )
        );

        $arBasketItems = array();

        while ($arItem = $rsBasket->Fetch())
        {
            if (\CSaleBasketHelper::isSetItem($arItem))
                continue;
            $arBasketItems[] = $arItem;
        }

        $totalPrice = 0;

        if ($arBasketItems)
        {
            $arOrder = static::calculateBasket($arBasketItems);
            $totalPrice = $arOrder['ORDER_PRICE'];
        }

        return array(
            'NUM_PRODUCTS' => count($arBasketItems),
            'TOTAL_PRICE' => CurrencyFormat($totalPrice, $currency)
        );
    }

    public static function calculateBasket($arBasketItems)
    {
        \CModule::IncludeModule('sale');
        $totalPrice = 0;
        $totalWeight = 0;

        foreach ($arBasketItems as $arItem)
        {
            $totalPrice += $arItem["PRICE"] * $arItem["QUANTITY"];
            $totalWeight += $arItem["WEIGHT"] * $arItem["QUANTITY"];
        }

        $arOrder = array(
            'SITE_ID' => SITE_ID,
            'ORDER_PRICE' => $totalPrice,
            'ORDER_WEIGHT' => $totalWeight,
            'BASKET_ITEMS' => $arBasketItems
        );

        return $arOrder;
    }

    public static function filterTableNames($table_name, $arFields, $name_prefix = false, $unset_if_without_prefix = false, $use_cache = true) {
        global $DB;
        $arTableFields = array();
        if (is_string($name_prefix) && strlen($name_prefix)) {
            foreach ($arFields as $key => $field) {
                if (preg_match("/^".$name_prefix.("(.+)")."/",$field,$match)) {
                    if ($match[1] == "*") {
                        $arFields = array("*");
                        return $arFields;
                    }
                    unset($arFields[$key]);
                    $arFields[$key] = $match[1];
                } else if ($unset_if_without_prefix) {
                    unset($arFields[$key]);
                }
            }
        }
        $param_string = md5($table_name);

        if ($use_cache) $arTableFields = static::getCache(__FUNCTION__.$param_string.'_prop');

        if (empty($arTableFields['RESULT']))
        {
            $dbResult = $DB->Query("select COLUMN_NAME from information_schema.columns where table_name = \"$table_name\" order by ordinal_position");
            while ($next = $dbResult->GetNext()) {
                $arTableFields['RESULT'][] = $next['COLUMN_NAME'];
            }
            static::setCache(__FUNCTION__.$param_string.'_prop', $arTableFields['RESULT']);
        }
        return array_values(array_intersect($arTableFields['RESULT'],array_values($arFields)));
    }
    public static function buildUserAdminLink($user_id) {
        return "/bitrix/admin/user_edit.php?lang=".LANGUAGE_ID."&ID=".$user_id;
    }
    public static function checkProgram($program, &$output) {
        $output = shell_exec("which ".$program);
        if (preg_match("/\/usr\/bin\/which\: no /",$output)) {
            return false;
        } else {
            return true;
        }
    }
    public static function findProgram($program) {
        $output = "";
        if (static::checkProgram($program,$output)) {
            return preg_replace("/\n$/","",$output);
        } else {
            return false;
        }
    }
    public static function getMediaDuration($file,$format = "H:i:s",$use_cache = true) {
        $param_string = serialize(func_get_args());
        $program = static::findProgram("ffmpeg");
        if ($program) {
            if (file_exists($file)) {
                if ($use_cache) $result = static::getCache(__FUNCTION__.$param_string);
                if (empty($result['RESULT'])) {
                    $output = shell_exec($program." -i ".$file."  2>&1");
                    if (preg_match("/Duration: (\d\d:\d\d:\d\d)/",$output, $match)) {
                        if (!empty($format)) {
                            $result['RESULT'] = static::convert_strtime($match[1],"H:i:s",$format);
                        } else {
                            $result['RESULT'] = $match[1];
                        }
                    }
                    static::setCache(__FUNCTION__.$param_string, $result['RESULT']);
                }
                return $result['RESULT'];
            }
        }
        return false;
    }
    public static function getMediaDurationInSeconds($file, $use_cache = true) {
        $duration = static::getMediaDuration($file, "H:i:s", $use_cache);
        $arr_dur = explode(":",$duration);
        return intval($arr_dur[0])*3600+intval($arr_dur[1])*60+intval($arr_dur[2]);
    }
    public static function getFirstVideoImageAsData($file, $width, $height, $for_html = true, $use_cache = true) {
        return static::getFrameAsData($file,$width, $height, 1, $for_html, $use_cache);
    }
    public static function getFrameAsData($file, $width, $height, $seconds,$for_html = true, $use_cache = true) {
        if ($program = static::findProgram("ffmpeg")) {
            if (file_exists($file)) {
                $param_string = serialize(func_get_args());
                //$hash = sha1(md5_file($file).md5($param_string));
                $time = static::seconds_to_format($seconds);
                $output = abs_path("/upload/frames/out".md5($param_string).".jpg");
                if ($use_cache) $result = static::getCache(__FUNCTION__.$param_string);
                if (empty($result['RESULT'])) {
                    $comm = $program." -ss $time -i $file -frames:v 1 $output";
                    exec($comm);
                    if (file_exists($output)) {
                        if (intval($width) && intval($height)) {
                            $obFile = new CFile();
                            $arFile = \CFile::MakeFileArray($output);
                            $checkfile = $obFile->CheckFile($arFile,400000,'image/','gif,png,jpeg,jpg');
                            if (empty($checkfile)) {
                                $obFile->ResizeImage(
                                    $arFile, // путь к изображению, сюда же будет записан уменьшенный файл
                                    array(
                                        "width" => $width,  // новая ширина
                                        "height" => $height // новая высота
                                    ),
                                    BX_RESIZE_IMAGE_EXACT // метод масштабирования. обрезать прямоугольник без учета пропорций
                                );
                                $output = $arFile['tmp_name'];
                            }
                        }
                        $base64data = static::getBase64File($output, $for_html);
                        if (!empty($base64data)) {
                            $result['RESULT'] = $base64data;
                            unlink($output);
                            static::setCache(__FUNCTION__.$param_string, $result['RESULT']);
                        }
                    }
                }
                return $result['RESULT'];
            }
        }
        return false;
    }
    public static function getBase64File($file, $for_html = true, $mime = "image/") {
        $type = static::getExtension($file);
        if (!empty($type)) {
            $data = file_get_contents($file);
            if ($for_html) {
                return 'data:'.$mime . $type . ';base64,' . base64_encode($data);
            } else {
                return base64_encode($data);
            }
        }
        return false;
    }
    public static function getExtension($file) {
        if (file_exists($file)) return pathinfo($file, PATHINFO_EXTENSION);
        return false;
    }
    public static function  convert_strtime($strtime, $from, $to) {
        return \DateTime::createFromFormat($from,$strtime)->format($to);
    }

    public  static function  build_cli_args($var) {
        return str_replace("&","\\&",urldecode(http_build_query($var)));
    }

    public static function seconds_to_format($seconds, $format = "H:i:s") {
        if (intval($seconds))
            return date($format,strtotime(date("d.m.Y 00:00:00", time()))+$seconds);
        else
            return false;
    }
    public static function bx_format_price($price_codes, $arItem) {
        $arCatalogPrices = \CIBlockPriceTools::GetCatalogPrices($arItem['IBLOCK_ID'], $price_codes);
        return \CIBlockPriceTools::GetItemPrices($arItem['IBLOCK_ID'], $arCatalogPrices, $arItem);
    }
    public static function trace($show_args=false, $for_web=true, $return=false){
        if ($for_web){
            $before = '<b>';
            $after = '</b>';
            $tab = '&nbsp;&nbsp;&nbsp;&nbsp;';
            $newline = '<br>';
        }
        else{
            $before = '<';
            $after = '>';
            $tab = "\t";
            $newline = "\n";
        }
        $output = '';
        $ignore_functions = array('include','include_once','require','require_once');
        $backtrace = debug_backtrace();
        $length = count($backtrace);

        for ($i=0; $i<$length; $i++){
            $function = $line = '';
            $skip_args = false;
            $caller = @$backtrace[$i+1]['function'];
            // Display caller function (if not a require or include)
            if(isset($caller) && !in_array($caller, $ignore_functions)){
                $function = ' in function '.$before.$caller.$after;
            }
            else{
                $skip_args = true;
            }
            $line = $before.$backtrace[$i]['file'].$after.$function .' on line: '.$before.$backtrace[$i]['line'].$after.$newline;
            if ($i < $length-1){
                if ($show_args && $backtrace[($i+1)]['args'] && !$skip_args){
                    $params = ($for_web) ? htmlentities(print_r($backtrace[($i+1)]['args'], true))
                        : print_r($backtrace[($i+1)]['args'], true);
                    $line .= $tab.'Called with params: '.preg_replace('/(\n)/',$newline.$tab,trim($params)).$newline.$tab.'By:'.$newline;
                    unset($params);
                }
                else{
                    $line .= $tab.'Called By:'.$newline;
                }
            }
            if ($return){
                $output .= $line;
            }
            else{
                echo $line;
            }
        }
        if ($return){
            return $output;
        }
    }

}

class CIBlockPirogovProperty extends \CIBlockProperty {
    public static function GetList($arOrder=Array(), $arFilter=Array())
    {
        global $DB;

        $strSql = "
			SELECT BP.*
			FROM b_iblock_property BP
		";

        $bJoinIBlock = false;
        $arSqlSearch = "";
        foreach($arFilter as $key => $val)
        {
            if (!is_array($val) || intval($val[0])) {
                $val = $DB->ForSql($val);
            }
            $key = strtoupper($key);

            switch($key)
            {
                case "ACTIVE":
                case "SEARCHABLE":
                case "FILTRABLE":
                case "IS_REQUIRED":
                case "MULTIPLE":
                    if($val=="Y" || $val=="N")
                        $arSqlSearch[] = "BP.".$key." = '".$val."'";
                    break;
                case "?CODE":
                case "?NAME":
                    $arSqlSearch[] = \CIBlock::FilterCreate("BP.".substr($key, 1), $val, "string", "E");
                    break;
                case "CODE":
                case "NAME":
                    $arSqlSearch[] = "UPPER(BP.".$key.") LIKE UPPER('".$val."')";
                    break;
                case "XML_ID":
                case "EXTERNAL_ID":
                    $arSqlSearch[] = "BP.XML_ID LIKE '".$val."'";
                    break;
                case "!XML_ID":
                case "!EXTERNAL_ID":
                    $arSqlSearch[] = "(BP.XML_ID IS NULL OR NOT (BP.XML_ID LIKE '".$val."'))";
                    break;
                case "TMP_ID":
                    $arSqlSearch[] = "BP.TMP_ID LIKE '".$val."'";
                    break;
                case "!TMP_ID":
                    $arSqlSearch[] = "(BP.TMP_ID IS NULL OR NOT (BP.TMP_ID LIKE '".$val."'))";
                    break;
                case "PROPERTY_TYPE":
                    $ar = explode(":", $val);
                    if(count($ar) == 2)
                    {
                        $val = $ar[0];
                        $arSqlSearch[] = "BP.USER_TYPE = '".$val[1]."'";
                    }
                    $arSqlSearch[] = "BP.".$key." = '".$val."'";
                    break;
                case "USER_TYPE":
                    $arSqlSearch[] = "BP.".$key." = '".$val."'";
                    break;
                case "ID":
                    if (is_array($val)) {
                        $str = "BP.".$key." IN (";
                        $str .= implode(", ",$val).")";
                    } else {
                        $str = "BP.".$key." = ".intval($val);
                    }
                    $arSqlSearch[] = $str;
                    break;
                case "IBLOCK_ID":
                case "LINK_IBLOCK_ID":
                case "VERSION":
                    $arSqlSearch[] = "BP.".$key." = ".intval($val);
                    break;
                case "IBLOCK_CODE":
                    $arSqlSearch[] = "UPPER(B.CODE) = UPPER('".$val."')";
                    $bJoinIBlock = true;
                    break;
            }
        }

        if($bJoinIBlock)
            $strSql .= "
				INNER JOIN b_iblock B ON B.ID = BP.IBLOCK_ID
			";

        if(!empty($arSqlSearch))
            $strSql .= "
				WHERE ".implode("\n\t\t\t\tAND ", $arSqlSearch)."
			";

        $arSqlOrder = array();
        foreach($arOrder as $by => $order)
        {
            $by = strtoupper($by);
            $order = strtoupper($order) == "ASC"? "ASC": "DESC";

            if(
                $by === "ID"
                || $by === "BLOCK_ID"
                || $by === "NAME"
                || $by === "ACTIVE"
                || $by === "SORT"
                || $by === "FILTRABLE"
                || $by === "SEARCHABLE"
            )
                $arSqlOrder[] = " BP.".$by." ".$order;
            else
                $arSqlOrder[] = " BP.TIMESTAMP_X ".$order;
        }

        DelDuplicateSort($arSqlOrder);

        if(!empty($arSqlOrder))
            $strSql .= "
				ORDER BY ".implode(", ", $arSqlOrder)."
			";
        $res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
        $res = new \CIBlockPropertyResult($res);
        return $res;
    }
}
?>
