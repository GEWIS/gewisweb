<?php

declare(strict_types=1);

namespace Decision\Model\Enums;

use function array_column;
use function array_combine;
use function array_map;
use function array_merge;

/**
 * Enum for the different many postal regions around the world. Based on data from PostNL and Wikipedia. Note: not all
 * of these regions use ZIP codes as part of their address format.
 */
enum PostalRegions: string
{
    case Afghanistan = 'AFGHANISTAN';
    case ÅlandIslands = 'ÅLAND ISLANDS';
    case Albania = 'ALBANIA';
    case Algeria = 'ALGERIA';
    case AmericanSamoa = 'AMERICAN SAMOA';
    case AmericanVirginIslands = 'AMERICAN VIRGIN ISLANDS';
    case Andorra = 'ANDORRA';
    case Angola = 'ANGOLA';
    case Anguilla = 'ANGUILLA';
    case Antarctica = 'ANTARCTICA';
    case AntiguaBarbuda = 'ANTIGUA AND BARBUDA';
    case Argentina = 'ARGENTINA';
    case Armenia = 'ARMENIA';
    case Aruba = 'ARUBA';
    case Ascension = 'ASCENSION';
    case Australia = 'AUSTRALIA';
    case Austria = 'AUSTRIA';
    case Azerbaijan = 'AZERBAIJAN';
    case Azores = 'AZORES';
    case Bahamas = 'BAHAMAS';
    case Bahrain = 'BAHRAIN';
    case BalearicIslands = 'BALEARIC ISLANDS';
    case Bangladesh = 'BANGLADESH';
    case Barbados = 'BARBADOS';
    case Belarus = 'BELARUS';
    case Belgium = 'BELGIUM';
    case Belize = 'BELIZE';
    case Benin = 'BENIN';
    case Bermuda = 'BERMUDA';
    case Bhutan = 'BHUTAN';
    case Bolivia = 'BOLIVIA';
    case Bonaire = 'BONAIRE'; // Saba and Saint Eustasius are also part of BQ but listed separately.
    case BosniaHerzegovina = 'BOSNIA AND HERZEGOVINA';
    case Botswana = 'BOTSWANA';
    case Bouvet = 'BOUVET';
    case Brazil = 'BRAZIL';
    case BritishIndianOceanTerritory = 'BRITISH INDIAN OCEAN TERRITORY';
    case BritishVirginIslands = 'BRITISH VIRGIN ISLANDS';
    case Brunei = 'BRUNEI';
    case Bulgaria = 'BULGARIA';
    case BurkinaFaso = 'BURKINA FASO';
    case Burundi = 'BURUNDI';
    case Cambodia = 'CAMBODIA';
    case Cameroon = 'CAMEROON';
    case Canada = 'CANADA';
    case CanaryIslands = 'CANARY ISLANDS';
    case CapeVerde = 'CAPE VERDE';
    case CaymanIslands = 'CAYMAN ISLANDS';
    case CentralAfricanRepublic = 'CENTRAL AFRICAN REPUBLIC';
    case Ceuta = 'CEUTA, SPAIN'; // Melila is listed separately.
    case Chad = 'CHAD';
    case Chile = 'CHILE';
    case China = 'CHINA';
    case ChristmasIsland = 'CHRISTMAS ISLAND';
    case CoconutIslands = 'COCONUT ISLANDS';
    case Colombia = 'COLOMBIA';
    case Comores = 'COMORES';
    case CongoBrazzaville = 'CONGO (REP.)';
    case CongoKinshasa = 'CONGO (DEM. REP.)';
    case CookIslands = 'COOK ISLANDS';
    case CostaRica = 'COSTA RICA';
    case CôtedIvoire = 'CÔTE D\'IVOIRE (REP.)'; // Ivory Coast
    case Croatia = 'CROATIA';
    case Cuba = 'CUBA';
    case Curaçao = 'CURAÇAO';
    case Cyprus = 'CYPRUS';
    case CzechRepublic = 'CZECH REPUBLIC';
    case Denmark = 'DENMARK';
    case Djibouti = 'DJIBOUTI';
    case Dominica = 'DOMINICA';
    case DominicanRepublic = 'DOMINICAN REPUBLIC';
    case Ecuador = 'ECUADOR';
    case Egypt = 'EGYPT';
    case ElSalvador = 'EL SALVADOR';
    case EquatorialGuinea = 'EQUATORIAL GUINEA';
    case Eritrea = 'ERITREA';
    case Estonia = 'ESTONIA';
    case Ethiopia = 'ETHIOPIA';
    case FalklandIslands = 'FALKLAND ISLANDS';
    case FaroeIslands = 'FAROE ISLANDS';
    case Fiji = 'FIJI';
    case Finland = 'FINLAND';
    case France = 'FRANCE';
    case FrenchGuiana = 'FRENCH GUIANA'; // Technically FRANCE but this is also accepted.
    case FrenchPolynesia = 'FRENCH POLYNESIA';
    case FrenchSouthernAntarcticLands = 'FRENCH SOUTHERN LANDS';
    case Gabon = 'GABON';
    case Gambia = 'GAMBIA';
    case Georgia = 'GEORGIA';
    case Germany = 'GERMANY';
    case Ghana = 'GHANA';
    case Gibraltar = 'GIBRALTAR';
    case Greece = 'GREECE';
    case Greenland = 'GREENLAND';
    case Grenada = 'GRENADA';
    case Guadeloupe = 'GUADELOUPE';
    case Guam = 'GUAM';
    case Guatemala = 'GUATEMALA';
    case Guernsey = 'GUERNSEY, CHANNEL ISLANDS'; // Part of the Channel Islands but all listed separately.
    case Guinea = 'GUINEA';
    case GuineaBissau = 'GUINEA-BISSAU';
    case Guyana = 'GUYANA';
    case Haiti = 'HAITI';
    case Honduras = 'HONDURAS';
    case Hongkong = 'HONGKONG';
    case Hungary = 'HUNGARY';
    case Iceland = 'ICELAND';
    case India = 'INDIA';
    case Indonesia = 'INDONESIA';
    case Iran = 'IRAN';
    case Iraq = 'IRAQ';
    case Ireland = 'IRELAND';
    case IsleOfMan = 'ISLE OF MAN';
    case Israel = 'ISRAEL';
    case Italy = 'ITALY';
    case Jamaica = 'JAMAICA';
    case Japan = 'JAPAN';
    case Jersey = 'JERSEY, CHANNEL ISLANDS'; // Part of the Channel Islands but all listed separately.
    case Jordan = 'JORDAN';
    case Kazakhstan = 'KAZAKHSTAN'; // Postcode should be below the country name
    case Kenya = 'KENYA';
    case Kiribati = 'KIRIBATI';
    case Kosovo = 'KOSOVO';
    case Kuwait = 'KUWAIT';
    case Kyrgyzstan = 'KYRGYZSTAN';
    case Laos = 'LAOS';
    case Latvia = 'LATVIA';
    case Lebanon = 'LEBANON';
    case Lesotho = 'LESOTHO';
    case Liberia = 'LIBERIA';
    case Libia = 'LIBIA';
    case Liechtenstein = 'LIECHTENSTEIN';
    case Lithuania = 'LITHUANIA';
    case Luxembourg = 'LUXEMBOURG';
    case Macau = 'MACAU';
    case Madagascar = 'MADAGASCAR';
    case Madeira = 'MADEIRA';
    case Malawi = 'MALAWI';
    case Malaysia = 'MALAYSIA';
    case Maldives = 'MALDIVES';
    case Mali = 'MALI';
    case Malta = 'MALTA';
    case MarshallIslands = 'MARSHALL ISLANDS';
    case Martinique = 'MARTINIQUE';
    case Mauritania = 'MAURITANIA';
    case Mauritius = 'MAURITIUS';
    case Mayotte = 'MAYOTTE'; // Technically FRANCE but this is also accepted.
    case Mexico = 'MEXICO';
    case Micronesia = 'MICRONESIA';
    case Moldavia = 'MOLDAVIA';
    case Monaco = 'MONACO';
    case Mongolia = 'MONGOLIA';
    case Montenegro = 'MONTENEGRO';
    case Montserrat = 'MONTSERRAT';
    case Morocco = 'MOROCCO';
    case Mozambique = 'MOZAMBIQUE';
    case Myanmar = 'MYANMAR';
    case Namibia = 'NAMIBIA';
    case Nauru = 'NAURU';
    case Nepal = 'NEPAL';
    case Netherlands = 'NETHERLANDS';
    case NewCaledonia = 'NEW CALEDONIA';
    case NewZealand = 'NEW ZEALAND';
    case Nicaragua = 'NICARAGUA';
    case Niger = 'NIGER';
    case Nigeria = 'NIGERIA';
    case Niue = 'NIUE'; // Technically NEW ZEALAND but this is also accepted.
    case NorfolkIsland = 'NORFOLK ISLAND';
    case NorthKorea = 'DEM. PEOPLE\'S REP. OF KOREA';
    case NorthMacedonia = 'REPUBLIC OF NORTH MACEDONIA';
    case NorthernMarianaIslands = 'NORTHERN MARIANA ISLANDS';
    case Norway = 'NORWAY';
    case Oman = 'OMAN';
    case Pakistan = 'PAKISTAN';
    case Palestine = 'PALESTINE';
    case Panama = 'PANAMA';
    case PapuaNewGuinea = 'PAPUA NEW GUINEA';
    case Paraguay = 'PARAGUAY';
    case Peru = 'PERU';
    case Philippines = 'PHILIPPINES';
    case PitcairnIslands = 'PITCAIRN ISLANDS';
    case Poland = 'POLAND';
    case Portugal = 'PORTUGAL';
    case PuertoRico = 'PUERTO RICO';
    case Qatar = 'QATAR';
    case Romania = 'ROMANIA';
    case Russia = 'RUSSIAN FEDERATION';
    case Rwanda = 'RWANDA';
    case Saba = 'SABA'; // BQ
    case SaintHelena = 'SAINT HELENA';
    case SaintKittsNevis = 'ST. KITTS AND NEVIS';
    case SaintLucia = 'SAINT LUCIA';
    case SaintMartin = 'SAINT MARTIN'; // Technically FRANCE but this is also accepted.
    case SaintVincentGrenadines = 'SAINT VINCENT AND THE GRENADINES';
    case SalomonIslands = 'SALOMON ISLANDS';
    case Samoa = 'SAMOA';
    case SanMarino = 'SAN MARINO';
    case SaudiArabia = 'SAUDI ARABIA';
    case Senegal = 'SENEGAL';
    case Serbia = 'SERBIA';
    case Seychelles = 'SEYCHELLES';
    case SierraLeone = 'SIERRA LEONE';
    case Singapore = 'SINGAPORE';
    case SintEustatius = 'SINT EUSTATIUS'; // BQ
    case Slovakia = 'SLOVAKIA';
    case Slovenia = 'SLOVENIA';
    case Somalia = 'SOMALIA';
    case SouthAfrica = 'SOUTH AFRICA';
    case SouthKorea = 'REP. OF KOREA';
    case SouthSudan = 'SOUTH SUDAN';
    case Spain = 'SPAIN';
    case SriLanka = 'SRI LANKA';
    case Sudan  = 'SUDAN';
    case Suriname = 'SURINAME';
    case SvalbardJanMayen = 'SVALBARD AND JAN MAYEN';
    case Swaziland = 'SWAZILAND';
    case Sweden = 'SWEDEN';
    case Switzerland = 'SWITZERLAND';
    case Syria = 'SYRIA';
    case Taiwan = 'TAIWAN';
    case Tajikistan = 'TAJIKISTAN';
    case Tanzania = 'TANZANIA';
    case Thailand = 'THAILAND';
    case TimorLeste = 'TIMOR-LESTE';
    case Togo = 'TOGO';
    case Tokelau = 'TOKELAU'; // Technically NEW ZEALAND but this is also accepted.
    case Tonga = 'TONGA';
    case TrinidadTobago = 'TRINIDAD AND TOBAGO';
    case Tunisia = 'TUNISIA';
    case Turkiye = 'TURKIYE';
    case Turkmenistan = 'TURKMENISTAN';
    case Tuvalu = 'TUVALU';
    case Uganda = 'UGANDA';
    case Ukraine = 'UKRAINE';
    case UnitedArabEmirates = 'UNITED ARAB EMIRATES';
    case UnitedKingdom = 'UNITED KINGDOM';
    case UnitedStates = 'UNITED STATES OF AMERICA';
    case Uruguay = 'URUGUAY';
    case Uzbekistan = 'UZBEKISTAN';
    case Vanuatu = 'VANUATU';
    case VaticanCity = 'VATICAN CITY';
    case Venezuela = 'VENEZUELA';
    case Vietnam = 'VIETNAM';
    case WesternSahara = 'WESTERN SAHARA';
    case Yemen = 'YEMEN';
    case Zambia = 'ZAMBIA';
    case Zanzibar = 'ZANZIBAR';
    case Zimbabwe = 'ZIMBABWE';

    /**
     * @return string[]
     */
    public static function formValues(): array
    {
        $values = array_column(self::cases(), 'value');

        return array_combine($values, $values);
    }

    /**
     * @return array<array-key, PostalRegions|string>
     */
    public static function values(): array
    {
        return array_merge(
            array_map(
                static fn (self $status) => $status->value,
                self::cases(),
            ),
            self::cases(),
        );
    }
}
