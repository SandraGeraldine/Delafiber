<?php
if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $days = $diff->d;
        $weeks = floor($days / 7);
        $days = $days - ($weeks * 7);

        $string = [
            'y' => 'año',
            'm' => 'mes',
            'w' => 'semana',
            'd' => 'día',
            'h' => 'hora',
            'i' => 'minuto',
            's' => 'segundo',
        ];
        $result = [];
        foreach ($string as $k => $v) {
            if ($k == 'w' && $weeks) {
                $result[] = $weeks . ' ' . $v . ($weeks > 1 ? 's' : '');
            } elseif ($k == 'd' && $days) {
                $result[] = $days . ' ' . $v . ($days > 1 ? 's' : '');
            } elseif ($k != 'w' && $k != 'd' && $diff->$k) {
                $result[] = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            }
        }

        if (!$full) $result = array_slice($result, 0, 1);
        return $result ? 'hace ' . implode(', ', $result) : 'justo ahora';
    }
}

if (!function_exists('timeAgo')) {
    /**
     * Convertir timestamp a formato "hace X tiempo"
     * 
     * @param string $datetime Fecha en formato Y-m-d H:i:s
     * @return string Tiempo relativo en español
     */
    function timeAgo($datetime) {
        if (empty($datetime)) {
            return '';
        }
        
        $time = strtotime($datetime);
        if ($time === false) {
            return $datetime;
        }
        
        $diff = time() - $time;
        
        if ($diff < 60) {
            return 'Hace un momento';
        }
        
        if ($diff < 3600) {
            $mins = floor($diff / 60);
            return 'Hace ' . $mins . ($mins == 1 ? ' minuto' : ' minutos');
        }
        
        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return 'Hace ' . $hours . ($hours == 1 ? ' hora' : ' horas');
        }
        
        if ($diff < 604800) {
            $days = floor($diff / 86400);
            $texto = ($days == 1) ? ' día' : ' días';
            return 'Hace ' . $days . $texto;
        }
        
        // Si es más de una semana, mostrar fecha completa
        return date('d/m/Y H:i', $time);
    }
}
