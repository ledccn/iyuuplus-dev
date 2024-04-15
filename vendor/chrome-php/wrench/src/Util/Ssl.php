<?php

namespace Wrench\Util;

class Ssl
{
    /**
     * Generates a new PEM File given the information.
     *
     * @param string      $pem_file                 the path of the PEM file to create
     * @param string|null $pem_passphrase           the passphrase to protect the PEM file or if you don't want to use a
     *                                              passphrase
     * @param string      $country_name             the country code of the new PEM file. e.g.: EN
     * @param string      $state_or_province_name   the state or province name of the new PEM file
     * @param string      $locality_name            the name of the locality
     * @param string      $organization_name        the name of the organisation. e.g.: MyCompany
     * @param string      $organizational_unit_name the organisation unit name
     * @param string      $common_name              the common name
     * @param string      $email_address            the email address
     */
    public static function generatePemFile(
        string $pem_file,
        ?string $pem_passphrase,
        string $country_name,
        string $state_or_province_name,
        string $locality_name,
        string $organization_name,
        string $organizational_unit_name,
        string $common_name,
        string $email_address
    ): void {
        // Generate PEM file
        $dn = [
            'countryName' => $country_name,
            'stateOrProvinceName' => $state_or_province_name,
            'localityName' => $locality_name,
            'organizationName' => $organization_name,
            'organizationalUnitName' => $organizational_unit_name,
            'commonName' => $common_name,
            'emailAddress' => $email_address,
        ];

        $privkey = \openssl_pkey_new();
        $cert = \openssl_csr_new($dn, $privkey);
        $cert = \openssl_csr_sign($cert, null, $privkey, 365);

        $pem = [0 => '', 1 => ''];

        \openssl_x509_export($cert, $pem[0]);
        \openssl_pkey_export($privkey, $pem[1], $pem_passphrase);

        \file_put_contents($pem_file, \implode('', $pem));
    }
}
