<?php defined('DIRECTACCESS') OR die('No direct script access.');

class Str {
    
    static public function Distance($sRow, $sCol)
    {
        $rowLen = strlen($sRow);
        $colLen = strlen($sCol);
        
        if($rowLen==0) return $colLen;
        if($colLen==0) return $rowLen;
        
        $v0 = [];
        $v1 = [];
        $vTmp = [];
        
        for($rowIdx = 0; $rowIdx <= $rowLen; $rowIdx++)
            $v0[$rowIdx] = $rowIdx;
        
        for($colIdx = 1; $colIdx <= $colLen; $colIdx++)
        {
            $v1[0] = $colIdx;
            $col_j = $sCol[$colIdx - 1];

            for ($rowIdx = 1; $rowIdx <= $rowLen; $rowIdx++)
            {
                $row_i = $sRow[$rowIdx - 1];
                $cost = ($row_i == $col_j) ? 0 : 1;
                
                $m_min = $v0[$rowIdx] + 1;
                $b = $v1[$rowIdx - 1] + 1;
                $c = $v0[$rowIdx - 1] + $cost;

                if ($b < $m_min) $m_min = $b;
                if ($c < $m_min) $m_min = $c;
                $v1[$rowIdx] = $m_min;
            }
            $vTmp = $v0;
            $v0 = $v1;
            $v1 = $vTmp;
        }

        return $v0[$rowLen];
    }
    
    
    static $normalizeChars = [
        'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
        'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
        'Ï'=>'I', 'Ñ'=>'N', 'Ń'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
        'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
        'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
        'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ń'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
        'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f',
        'ă'=>'a', 'î'=>'i', 'â'=>'a', 'ș'=>'s', 'ț'=>'t', 'Ă'=>'A', 'Î'=>'I', 'Â'=>'A', 'Ș'=>'S', 'Ț'=>'T', '&'=>'e'
    ];
    
    
    
    
    static public function Clean($str, $space) 
    {
        $str = self::RemoveAccent($str);
        $str = strtolower($str);
        $str = self::DeRomanize($str);        
        $str = preg_replace('/[^a-z0-9]/', ' ', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        $str = str_replace(" ", $space, $str);
        $str = trim($str);
        return $str;
    }
    
    static public function RemoveAccent($str) 
    {
        return  strtr($str, self::$normalizeChars);
    }
    
    static $ignore_deroman = [ "mx", "atv", "civ", "rebirth", "series" ];
    static public function DeRomanize($str)
    {
        foreach(self::$ignore_deroman as $id)
            if(str_contains($id, $str))
               return $str;
                    
        $arr = explode(" ", $str);
        for($i=0; $i<count($arr); $i++)        
            $arr[$i] = self::DeRomanizeWord($arr[$i]);
        
        return implode(" ", $arr);
    }
    
    static $romanMap = [ 'I'=>1, 'V'=>5, 'X'=>10, 'L'=>50, 'C'=>100, 'D'=>500, 'M'=>1000];
            
    static public function DeRomanizeWord($str)
    {
        $str = strtoupper($str);
        if($str=="I") return "i";
        if($str=="C") return "c";
        if($str=="M") return "m";
        if($str=="DC") return "dc";
        
        if($str=="" ||  !preg_match("/^M*(?:D?C{0,3}|C[MD])(?:L?X{0,3}|X[CL])(?:V?I{0,3}|I[XV])$/", $str))
                return strtolower($str);

        $number = 0;
        $previousChar = $str[0];

        $len = strlen($str);
        for($i=0; $i<$len; $i++)
        {
            $currentChar = $str[$i];
            $number += self::$romanMap[$currentChar];
            if (self::$romanMap[$previousChar] < self::$romanMap[$currentChar])
                $number -= self::$romanMap[$previousChar] * 2;
            $previousChar = $currentChar;
        }
        if($number==1) return "I";
        return strval($number);
    }
    
    static public function GetSubstrings($s, $size)
    {
            $ss = [];
            for($i=0; $i<=strlen($s)-$size; $i++)
                    $ss[] = substr ($s, $i, $size);
            return $ss;
    }
    
    function matchOrder(array $orig, array $test) 
    {
        $o=0;
        $t=0;
        $lenO = count($orig);
        $lenT = count($test);

        while ($o < $lenO && $t < $lenT) {
            if ($orig[$o] == $test[$t])
                $t++;
            $o++;
        }
        return ($t == $lenT);
    }

}