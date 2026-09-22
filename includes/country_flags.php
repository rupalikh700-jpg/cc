<?php
// includes/country_flags.php — Country emoji flag helper

function getCountryEmoji(string $country): string {
    static $flags = [
        'Afghanistan' => '🇦🇫', 'Albania' => '🇦🇱', 'Algeria' => '🇩🇿', 'Andorra' => '🇦🇩',
        'Angola' => '🇦🇴', 'Argentina' => '🇦🇷', 'Armenia' => '🇦🇲', 'Australia' => '🇦🇺',
        'Austria' => '🇦🇹', 'Azerbaijan' => '🇦🇿', 'Bahamas' => '🇧🇸', 'Bahrain' => '🇧🇭',
        'Bangladesh' => '🇧🇩', 'Belarus' => '🇧🇾', 'Belgium' => '🇧🇪', 'Belize' => '🇧🇿',
        'Bolivia' => '🇧🇴', 'Bosnia and Herzegovina' => '🇧🇦', 'Brazil' => '🇧🇷',
        'Bulgaria' => '🇧🇬', 'Cambodia' => '🇰🇭', 'Cameroon' => '🇨🇲', 'Canada' => '🇨🇦',
        'Chile' => '🇨🇱', 'China' => '🇨🇳', 'Colombia' => '🇨🇴', 'Croatia' => '🇭🇷',
        'Cuba' => '🇨🇺', 'Cyprus' => '🇨🇾', 'Czech Republic' => '🇨🇿', 'Denmark' => '🇩🇰',
        'Ecuador' => '🇪🇨', 'Egypt' => '🇪🇬', 'Estonia' => '🇪🇪', 'Ethiopia' => '🇪🇹',
        'Finland' => '🇫🇮', 'France' => '🇫🇷', 'Georgia' => '🇬🇪', 'Germany' => '🇩🇪',
        'Ghana' => '🇬🇭', 'Greece' => '🇬🇷', 'Guatemala' => '🇬🇹', 'Hungary' => '🇭🇺',
        'Iceland' => '🇮🇸', 'India' => '🇮🇳', 'Indonesia' => '🇮🇩', 'Iran' => '🇮🇷',
        'Iraq' => '🇮🇶', 'Ireland' => '🇮🇪', 'Israel' => '🇮🇱', 'Italy' => '🇮🇹',
        'Jamaica' => '🇯🇲', 'Japan' => '🇯🇵', 'Jordan' => '🇯🇴', 'Kazakhstan' => '🇰🇿',
        'Kenya' => '🇰🇪', 'Kuwait' => '🇰🇼', 'Latvia' => '🇱🇻', 'Lebanon' => '🇱🇧',
        'Lithuania' => '🇱🇹', 'Luxembourg' => '🇱🇺', 'Malaysia' => '🇲🇾', 'Malta' => '🇲🇹',
        'Mexico' => '🇲🇽', 'Moldova' => '🇲🇩', 'Monaco' => '🇲🇨', 'Mongolia' => '🇲🇳',
        'Morocco' => '🇲🇦', 'Myanmar' => '🇲🇲', 'Netherlands' => '🇳🇱', 'New Zealand' => '🇳🇿',
        'Nigeria' => '🇳🇬', 'Norway' => '🇳🇴', 'Oman' => '🇴🇲', 'Pakistan' => '🇵🇰',
        'Palestine' => '🇵🇸', 'Panama' => '🇵🇦', 'Peru' => '🇵🇪', 'Philippines' => '🇵🇭',
        'Poland' => '🇵🇱', 'Portugal' => '🇵🇹', 'Qatar' => '🇶🇦', 'Romania' => '🇷🇴',
        'Russia' => '🇷🇺', 'Saudi Arabia' => '🇸🇦', 'Serbia' => '🇷🇸', 'Singapore' => '🇸🇬',
        'Slovakia' => '🇸🇰', 'Slovenia' => '🇸🇮', 'Somalia' => '🇸🇴', 'South Africa' => '🇿🇦',
        'South Korea' => '🇰🇷', 'Spain' => '🇪🇸', 'Sri Lanka' => '🇱🇰', 'Sweden' => '🇸🇪',
        'Switzerland' => '🇨🇭', 'Syria' => '🇸🇾', 'Taiwan' => '🇹🇼', 'Thailand' => '🇹🇭',
        'Tunisia' => '🇹🇳', 'Turkey' => '🇹🇷', 'Ukraine' => '🇺🇦', 'United Arab Emirates' => '🇦🇪',
        'United Kingdom' => '🇬🇧', 'United States' => '🇺🇸', 'Uruguay' => '🇺🇾',
        'Uzbekistan' => '🇺🇿', 'Venezuela' => '🇻🇪', 'Vietnam' => '🇻🇳', 'Yemen' => '🇾🇪',
        'Zambia' => '🇿🇲', 'Zimbabwe' => '🇿🇼',
    ];
    return $flags[$country] ?? '🌍';
}
