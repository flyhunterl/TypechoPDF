<?php
/**
 * PDF文章阅读器 - 在文章中嵌入PDF阅读器
 * 
 * @package TypechoPDF
 * @author flyhunterl
 * @version 1.0.0
 * @link https://llingfei.com
 */

!defined('__TYPECHO_ROOT_DIR__') && exit();

class TypechoPDF_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     */
    public static function activate()
    {
        // 检查必要文件是否存在
        $requiredFiles = array(
            'static/pdfjs/build/pdf.js',
            'static/pdfjs/build/pdf.worker.js',
            'static/pdfjs/web/viewer.html',
            'static/pdfjs/web/viewer.css',
            'static/pdfjs/web/viewer.js',
        );
        
        $pluginDir = __DIR__;
        foreach ($requiredFiles as $file) {
            if (!file_exists($pluginDir . '/' . $file)) {
                throw new Typecho_Plugin_Exception(_t('缺少必要文件：' . $file));
            }
        }
        
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('TypechoPDF_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Archive')->header = array('TypechoPDF_Plugin', 'header');
        return _t('插件启用成功');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     */
    public static function deactivate()
    {
        return _t('插件禁用成功');
    }
    
    /**
     * 获取插件配置面板
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $width = new Typecho_Widget_Helper_Form_Element_Text(
            'width', 
            null, 
            '100%', 
            _t('PDF阅读器宽度'), 
            _t('可以使用百分比或具体像素值，例如: 100% 或 800px')
        );
        $form->addInput($width);
        
        $height = new Typecho_Widget_Helper_Form_Element_Text(
            'height', 
            null, 
            '600px', 
            _t('PDF阅读器高度'), 
            _t('建议使用像素值，例如: 600px')
        );
        $form->addInput($height);

        $lang = new Typecho_Widget_Helper_Form_Element_Select(
            'lang',
            array(
                'zh-CN' => _t('中文'),
                'en-US' => _t('English')
            ),
            'zh-CN',
            _t('界面语言'),
            _t('选择PDF阅读器的界面语言')
        );
        $form->addInput($lang);
    }
    
    /**
     * 个人用户的配置面板
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 添加头部资源
     */
    public static function header()
    {
        $options = Helper::options()->plugin('TypechoPDF');
        $pdfjs_url = Helper::options()->pluginUrl . '/TypechoPDF/static/pdfjs/web/viewer.html';
        
        $lang = isset($options->lang) ? $options->lang : 'zh-CN';
        
        echo '<style>
            .pdf-container {
                width: 100%;
                margin: 15px 0;
                background: #f5f5f5;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .pdf-viewer {
                width: 100%;
                border: none;
            }
        </style>';
        
        // 添加CSP头
        header("Content-Security-Policy: script-src 'self' 'unsafe-inline' 'unsafe-eval';");
    }
    
    /**
     * 解析内容
     */
    public static function parse($content, $widget, $lastResult)
    {
        $content = empty($lastResult) ? $content : $lastResult;
        
        if (preg_match_all('/<a[^>]*href=[\'"]([^\'"]*\.pdf)[\'"][^>]*>(.*?)<\/a>/i', $content, $matches)) {
            $options = Helper::options()->plugin('TypechoPDF');
            $width = $options->width;
            $height = $options->height;
            $lang = isset($options->lang) ? $options->lang : 'zh-CN';
            
            foreach ($matches[0] as $key => $match) {
                $pdf_url = $matches[1][$key];
                // 确保PDF URL是绝对路径
                if (!preg_match('/^https?:\/\//', $pdf_url)) {
                    $pdf_url = Helper::options()->siteUrl . ltrim($pdf_url, '/');
                }
                
                $viewer_url = Helper::options()->pluginUrl . '/TypechoPDF/static/pdfjs/web/viewer.html';
                $viewer_url .= '?file=' . urlencode($pdf_url);
                $viewer_url .= '&lang=' . $lang;
                
                $replacement = '<div class="pdf-container">';
                $replacement .= '<iframe class="pdf-viewer" src="' . $viewer_url . '" ';
                $replacement .= 'width="' . $width . '" height="' . $height . '" ';
                $replacement .= 'allowfullscreen webkitallowfullscreen></iframe>';
                $replacement .= '</div>';
                
                $content = str_replace($match, $replacement, $content);
            }
        }
        
        return $content;
    }
} 