<?php
class DescontoPixShortcode {
    public function parcelas_flexsconto_pix_shortcode() {
        // Verifica se temos um produto global ou se estamos dentro do loop de produtos
        $texto_a_vista = get_option('parcelas_flex_texto_a_vista', 'à vista');
        $texto_no_pix = get_option('parcelas_flex_texto_no_pix', 'no Pix');
        $product = wc_get_product(get_the_ID());

        // Se temos um produto válido
        if ($product && ($product instanceof WC_Product_Variable || $product instanceof WC_Product)) {
            // Prepara o HTML para resposta
            $output = "<div id='desconto-pix-container'>";

            // Se o produto for variável, tenta encontrar o preço padrão
            if ($product instanceof WC_Product_Variable) {
                $variacoes = $product->get_available_variations();
                $preco_minimo = null;

                foreach ($variacoes as $variacao) {
                    $variacao_obj = wc_get_product($variacao['variation_id']);
                    $preco_variacao = floatval($variacao_obj->get_price());

                    if (is_null($preco_minimo) || $preco_variacao < $preco_minimo) {
                        $preco_minimo = $preco_variacao;
                    }
                }

                if (!is_null($preco_minimo)) {
                    $desconto_pix = floatval(get_option('desconto_pix', 0));
                    $preco_com_desconto_pix = $preco_minimo * (1 - ($desconto_pix / 100));
                    $preco_formatado = wc_price($preco_com_desconto_pix);
                    $output .= '
                    <div class="opcao-pagamento pix" itemscope itemtype="https://schema.org/Offer">
                        <img src="' . plugin_dir_url(__FILE__) . '../src/imagem/icon-pix.svg" alt="Ícone de Pix" width="20" height="20">
                        <span class="parcelas">' . esc_html($texto_a_vista) . '</span>
                        <div class="desconto-container">
                            <span class="preco" itemprop="price">' . $preco_formatado . '</span>
                            <meta itemprop="priceCurrency" content="BRL">
                            <span class="textodesconto">' . esc_html($texto_no_pix) . '</span>
                            <div class="badge-container">
                                <div class="best-price__Badge-sc-1v0eo34-3 hWoKbG badge">
                                    <!-- SVG code -->
                                    -' . $desconto_pix . '%
                                </div>
                            </div>
                        </div>
                    </div>';
                    
                    
                } else {
                    $output .= "<p>Selecione uma opção de produto para ver o desconto do Pix.</p>";
                }
            } else {
                // Se o produto for simples, calcula o desconto
                $preco = floatval($product->get_price());
                $desconto_pix = floatval(get_option('desconto_pix', 0));
                $preco_com_desconto_pix = $preco * (1 - ($desconto_pix / 100));
                $preco_formatado = wc_price($preco_com_desconto_pix);
                $output .= '
                <div class="opcao-pagamento pix" itemscope itemtype="https://schema.org/Offer">
                    <img src="' . plugin_dir_url(__FILE__) . '../src/imagem/icon-pix.svg" alt="Ícone de Pix" width="20" height="20">
                    <span class="parcelas">' . esc_html($texto_a_vista) . '</span>
                    <div class="desconto-container">
                        <span class="preco" itemprop="price">' . $preco_formatado . '</span>
                        <meta itemprop="priceCurrency" content="BRL">
                        <span class="textodesconto">' . esc_html($texto_no_pix) . '</span>
                        <div class="badge-container">
                            <div class="best-price__Badge-sc-1v0eo34-3 hWoKbG badge">
                                <!-- SVG code -->
                                -' . $desconto_pix . '%
                            </div>
                        </div>
                    </div>
                </div>';
            }

            $output .= "</div>";

            // Retorna o HTML que será substituído pelo shortcode
            return $output;
        } else {
            // Se não estiver na página do produto ou o produto não for válido, retorna uma mensagem de erro
            return '<p>Desconto do Pix disponível apenas na página de produtos.</p>';
        }
    }

    public function buscar_desconto_pix() {
        $texto_a_vista = get_option('parcelas_flex_texto_a_vista', 'à vista');
        $texto_no_pix = get_option('parcelas_flex_texto_no_pix', 'no Pix');

        if (!isset($_POST['preco'])) {
            wp_send_json_error('Preço não foi enviado.');
            wp_die();
        }

        $preco = floatval($_POST['preco']);
        $desconto_pix = floatval(get_option('desconto_pix', 0));
        $preco_com_desconto_pix = $preco * (1 - ($desconto_pix / 100));
        $preco_formatado = wc_price($preco_com_desconto_pix);

        wp_send_json_success('
        <div class="opcao-pagamento pix" itemscope itemtype="https://schema.org/Offer">
            <img src="' . plugin_dir_url(__FILE__) . '../src/imagem/icon-pix.svg" alt="Ícone de Pix" width="20" height="20">
            <span class="parcelas">' . esc_html($texto_a_vista) . '</span>
            <span class="preco" itemprop="price">' . $preco_formatado . '</span>
            <meta itemprop="priceCurrency" content="BRL">
            <div class="desconto-container">
                <span class="textodesconto">' . esc_html($texto_no_pix) . '</span>
                <div class="badge-container">
                    <div class="best-price__Badge-sc-1v0eo34-3 hWoKbG badge">
                        <!-- SVG code -->
                        -' . $desconto_pix . '%
                    </div>
                </div>
            </div>
        </div>
    ');
    
        
        wp_die();
    }

    public function parcelas_flexsconto_pix_loop_shortcode() {
        $texto_a_vista = get_option('parcelas_flex_texto_a_vista', 'à vista');
        $texto_no_pix = get_option('parcelas_flex_texto_no_pix', 'no Pix');
        global $product;

        // Se não estamos dentro de um loop de produtos, tentamos obter o produto global
        if (!is_a($product, 'WC_Product')) {
            $product = wc_get_product(get_the_ID());
        }

        // Se ainda não temos um produto, retornamos uma mensagem de erro
        if (!is_a($product, 'WC_Product')) {
            return '<p>Desconto do Pix disponível apenas em loops de produtos.</p>';
        }

        $output = "<div class='desconto-pix-loop-container'>";

        // Verifica se o produto é variável ou simples
        if ($product->is_type('variable')) {
            $preco = floatval($product->get_variation_price('min', true)); // Preço mínimo da variação
        } else {
            $preco = floatval($product->get_price()); // Preço atual do produto
        }

        $desconto_pix = floatval(get_option('desconto_pix', 0));
        $preco_com_desconto_pix = $preco * (1 - ($desconto_pix / 100));
        $preco_formatado = wc_price($preco_com_desconto_pix);

        // Aqui você pode adicionar o HTML personalizado para o loop
        $output .= '
        <div class="opcao-pagamento pix" itemscope itemtype="https://schema.org/Offer">
            <img src="' . plugin_dir_url(__FILE__) . '../src/imagem/icon-pix.svg" alt="Ícone de Pix" width="20" height="20">
            <span class="parcelas">' . esc_html($texto_a_vista) . '</span>
            <div class="desconto-container">
                <span class="preco" itemprop="price">' . $preco_formatado . '</span>
                <meta itemprop="priceCurrency" content="BRL">
                <span class="textodesconto">' . esc_html($texto_no_pix) . '</span>
                <div class="badge-container">
                    <div class="best-price__Badge-sc-1v0eo34-3 hWoKbG badge">
                        <!-- SVG code -->
                        -' . $desconto_pix . '%
                    </div>
                </div>
            </div>
        </div>';
        

        return $output;
    }
}

$desconto_pix_shortcode = new DescontoPixShortcode();

add_shortcode('desconto_pix', array($desconto_pix_shortcode, 'parcelas_flexsconto_pix_shortcode'));
add_action('wp_ajax_buscar_desconto_pix', array($desconto_pix_shortcode, 'buscar_desconto_pix'));
add_action('wp_ajax_nopriv_buscar_desconto_pix', array($desconto_pix_shortcode, 'buscar_desconto_pix'));
add_shortcode('desconto_pix_loop', array($desconto_pix_shortcode, 'parcelas_flexsconto_pix_loop_shortcode'));
