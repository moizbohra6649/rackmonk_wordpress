<?php

/**
 * Domain
 *
 * @package xhosting
 * @author byteflows
 * @link https://byteflows.com
 */
class xhosting_Domain
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_nopriv_xhosting_ajax_search_domain', array($this, 'ajax_search_domain'));
        add_action('wp_ajax_xhosting_ajax_search_domain', array($this, 'ajax_search_domain'));
        add_action('wp_ajax_nopriv_xhosting_domain_search_seggestion', array($this, 'ajax_search_suggested_domain'));
        add_action('wp_ajax_xhosting_domain_search_seggestion', array($this, 'ajax_search_suggested_domain'));
        add_action('wp_head', array($this, 'domain_js_file'));
        add_action('wdes_domain_verify_code', array($this, 'verify_code'));

    }

    /**
     * Admin ajax for domain.
     */
    function domain_js_file()
    {
        echo '<script>
            /*<![CDATA[*/
            var xhosting_ajax_url = "' . admin_url('admin-ajax.php') . '";
            /*]]>*/
          </script>';
    }

    public function xhosting_whmcs_url_build($domain)
    {
        $theme_option = get_option('xhosting_theme_options');
        $whmcs_portal_url = !empty($theme_option['whmcs_portal_url']) ? $theme_option['whmcs_portal_url'] : '';
        $portal_url = esc_url($whmcs_portal_url) . '/cart.php?systpl=xhosting&carttpl=byteflows_cart&a=add&domain=register&query='.$domain;
        return $portal_url;
    }
    /**
     * Connect from whois server.
     *
     * @param string $domain the full domain that will checking.
     *
     * @return mixed|string
     */
    public function get_whois_server($domain)
    {

        $fp = stream_socket_client('whois.iana.org:43', $errno, $errstr, 30);
        if (!$fp) {
            echo "$errstr ($errno)<br />\n";
        } else {

            fputs($fp, $domain . "\r\n");
            $response_text = '';

            while (!feof($fp)) {
                $response_text .= fgets($fp, 128);
            }

            $response_server = '';

            if (strpos($response_text, 'but this server does not have') == 0) {
                if (strpos($domain, 'earth') == true) {
                    return $response_server = 'whois.nimzo98.com';
                }

                $split_whois        = explode('whois: ', $response_text);
                $split_status       = explode('status:', $split_whois[1]);
                $response_server    = preg_replace('/\s+/', '', $split_status[0]);
            }


            return $response_server;
        }
    }

    /**
     * Check if domain is available.
     *
     * @param $whois_server
     * @param $domain
     *
     * @return array
     */
    public function check_available($whois_server, $domain)
    {
        if ($whois_server && $domain) {

            $fp = stream_socket_client($whois_server . ':43', $errno, $errstr, 30);
            if (!$fp) {
                echo "$errstr ($errno)<br />\n";
            } else {
                fputs($fp, $domain . "\r\n");
                $response = '';

                while (!feof($fp)) {
                    $response .= fgets($fp, 128);
                }

                //Fix for work with country extension
                $lowercase_response = strtolower($response);

                if (is_int(strpos($lowercase_response, 'domain name:'))) {
                    $result = true;
                } else {
                    $result = false;
                }

                return array(
                    'status' => $result,
                );
            }
        } else {
            return array(
                'status' => false,
            );
        }

    }

    /**
     * Get the domain result by ajax.
     */
    public function ajax_search_domain()
    {
        $domain = sanitize_text_field(trim($_POST['domain']));

        $domainArr = explode('.', $domain);

        $ext      = '';
        $n_domain = '';
        if(count($domainArr) == 1) { // somedomain
            $ext = '.com';
            $n_domain = $domain . $ext;
        } else if (!empty($domainArr) && count($domainArr) == 2) { // somedomain.com
            $ext      = $domainArr[1];
            $n_domain = $domain;
        } else if (!empty($domainArr) && count($domainArr) == 3 && $domainArr[0] === 'www') { // www.somedomain.com
            $ext      = $domainArr[2];
            $n_domain = $domainArr[1] . '.' . $domainArr[2];
        }
        
        if(!empty($ext) && !empty($n_domain)) {
            $output = array(
                'status'        => '',
                'domain'        => '',
                'results_html'  => '',
            );

            $whois_server = $this->get_whois_server($n_domain);

            if (empty($whois_server)) {
                $output = array(
                    'status'        => 'no_support',
                    'domain'        => $domain,
                    'results_html'  => esc_html__('Please check the domain name or extension', 'xhosting-core'),
                );
            } else {
                $output = $this->check_available($whois_server, $n_domain);

                $output['status'] = ($output['status']) ? 'taken' : 'available';
                $output['domain'] = $n_domain;

                // result html.
                if ($output['status'] === 'available') {
                    
                    $output['results_html'] = '<div class="xhosting-s-result "><i class="far fa-check-circle"></i>' . __('Congratulation ', 'xhosting-core') . ' <b class="domain-find">' . $domain . ' </b>' . __('is available!', 'xhosting-core') . '<span class="xhosting-s-result-info"><a href="' . self::xhosting_whmcs_url_build($domain) . '" class="xhosting-btn" target="_blank"><input type="button" class="tg-btn tg-btn-two" value="' . esc_attr__('Purchase', 'xhosting-core') . '"></a></span></div>';
                } else {
                    $output['results_html'] = '<div class="xhosting-s-result alert-danger mb-3"><span class="xhosting-btn-token"><i class="fas fa-exclamation-circle icon-taken"></i><b >' . $domain . ' </b>' . ' ' . __('is unavailable', 'xhosting-core') . '</span>';
                }
            }

        } else {
            $output = array(
                'status'        => 'no_support',
                'domain'        => $domain,
                'results_html'  => esc_html__('Please check the domain name or extension', 'xhosting-core'),
            );
        }

        wp_send_json($output);
    }

    /**
     * Get the domain result by ajax.
     */
    public function ajax_search_suggested_domain()
    {
        $domain     = sanitize_text_field($_POST['domain']); // "somedomain"
        $domainArr  = explode('.', $domain);
        $exclodeExt = '.'.$domainArr[1];
        $basename   = $domainArr[0];
        $exts       = $_POST['extensions']; //[".org", ".net", ".online"]
        $domainList = [];


        if(!empty($exts)) {
            foreach($exts as $ext) {
                if($ext != $exclodeExt ){
                    $output = [
                        'status'        => '',
                        'domain'        => '',
                        'results_html'  => '',
                    ];

                    $n_domain     = $basename . $ext;
                    $whois_server = $this->get_whois_server($n_domain);
                    if(!empty($whois_server)) {
                        $output = $this->check_available($whois_server, $n_domain);

                        $output['status'] = ($output['status']) ? 'taken' : 'available';
                        $output['domain'] = $n_domain;

                        if ($output['status'] === 'available') {
                            $output['results_html'] = '<div class="xhosting-s-result"><svg width="20" height="21" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M2.5 10.5C2.5 11.4849 2.69399 12.4602 3.0709 13.3701C3.44781 14.2801 4.00026 15.1069 4.6967 15.8033C5.39314 16.4997 6.21993 17.0522 7.12987 17.4291C8.03982 17.806 9.01509 18 10 18C10.9849 18 11.9602 17.806 12.8701 17.4291C13.7801 17.0522 14.6069 16.4997 15.3033 15.8033C15.9997 15.1069 16.5522 14.2801 16.9291 13.3701C17.306 12.4602 17.5 11.4849 17.5 10.5C17.5 9.51509 17.306 8.53982 16.9291 7.62987C16.5522 6.71993 15.9997 5.89314 15.3033 5.1967C14.6069 4.50026 13.7801 3.94781 12.8701 3.5709C11.9602 3.19399 10.9849 3 10 3C9.01509 3 8.03982 3.19399 7.12987 3.5709C6.21993 3.94781 5.39314 4.50026 4.6967 5.1967C4.00026 5.89314 3.44781 6.71993 3.0709 7.62987C2.69399 8.53982 2.5 9.51509 2.5 10.5Z" stroke="#767982" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                                            <path d="M7.5 10.4999L9.16667 12.1666L12.5 8.83325" stroke="#404248" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                        </svg><b class="sg-v">' . $n_domain . ' </b>' . __('is available!', 'xhosting-core') . '<span class="xhosting-s-result-info"><a href="' . self::xhosting_whmcs_url_build($n_domain) . '" class="xhosting-btn" target="_blank"><input type="button" class="tg-btn tg-btn-two" value="' . esc_attr__('Purchase', 'xhosting-core') . '"></a></span></div>';
                        }

                        $domainList[] = $output;
                    }
                }
                
            }
        }

        wp_send_json($domainList);
    }

    /**
     * Verify code
     */
    public function verify_code()
    {
        echo '<input type="hidden" name="token" value="0811bf819565bf8142cc613d099e85a27ec1204c">';
    }


}

$obj = new xhosting_Domain();