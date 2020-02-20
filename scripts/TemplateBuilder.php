<?php
namespace silviulucian\infra;

class TemplateBuilder
{
    public static function rebuildTemplates()
    {
        $teraform_dir = Config::getTerraformDir();
        foreach (glob($teraform_dir.'/*', GLOB_BRACE) as $file) {
            unlink($file);
        }

        self::buildTemplates();
    }

    public static function buildTemplates()
    {
        $templates_dir = Config::getTemplatesDir();
        $terraform_dir = Config::getTerraformDir();

        $m = new \Mustache_Engine([
            'loader'           => new \Mustache_Loader_FilesystemLoader($templates_dir),
            'helpers'          => [
                'tfmspecialchars' => function ($text, \Mustache_LambdaHelper $helper) {
                    return Helpers::tfmspecialchars($helper->render($text));
                },
            ],
            'strict_callables' => false,
        ]);

        foreach (glob($templates_dir.'/*') as $file) {
            $template_file   = basename($file);
            $terraform__file = str_replace('.mustache', '', $template_file);

            $tpl = $m->loadTemplate($template_file);
            $tfm = $tpl->render(Config::get());

            file_put_contents($terraform_dir.'/'.$terraform__file, $tfm);
        }
    }
}
