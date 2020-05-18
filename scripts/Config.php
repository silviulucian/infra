<?php
namespace silviulucian\infra;

class Config
{
    private static $config = null;

    public static function get()
    {
        if (null === self::$config) {
            $cwd          = getcwd();
            $config_file  = $cwd.'/config.yml';
            $config_yml   = file_get_contents($config_file);
            self::$config = yaml_parse($config_yml);

            // Configure each service required for orchestrations
            if (isset (self::$config['orchestrations']) && is_array(self::$config['orchestrations'])) {
                foreach (self::$config['orchestrations'] as $orchestration) {
                    //
                    // The static-website orchestration
                    //-----------------------------------------------------------------------------

                    if ('static-website' === $orchestration['type']) {
                        // CloudFront
                        if (! isset (self::$config['cloud_front']) || ! is_array(self::$config['cloud_front'])) {
                            self::$config['cloud_front'] = [];
                        }

                        self::$config['cloud_front'][] = [
                            'name'   => $orchestration['name'],
                            'bucket' => $orchestration['name'],

                            'aliases' => [
                                $orchestration['name'],
                            ],

                            'acm_certificate_arn' => $orchestration['acm_certificate_arn'],
                        ];

                        // CodePipeline
                        if (! isset (self::$config['code_pipeline']) || ! is_array(self::$config['code_pipeline'])) {
                            self::$config['code_pipeline'] = [];
                        }

                        self::$config['code_pipeline'][] = [
                            'name'       => $orchestration['name'],
                            'repository' => $orchestration['repository'],
                            'branch'     => $orchestration['branch'],

                            'env' => [
                                [
                                    'variable' => 'BUCKET_NAME',
                                    'value'    => '"'.$orchestration['name'].'"',
                                ],
                                [
                                    'variable' => 'DISTRIBUTION_ID',
                                    'value'    => 'aws_cloudfront_distribution.'.Helpers::tfmspecialchars($orchestration['name']).'.id',
                                ],
                            ],
                        ];

                        // Route53
                        if (! isset (self::$config['route53']) || ! is_array(self::$config['route53'])) {
                            self::$config['route53'] = [];
                        }

                        $record = [
                            'zone'  => $orchestration['zone'],
                            'name'  => $orchestration['name'],
                            'alias' => $orchestration['name'],
                        ];

                        self::$config['route53'][] = array_merge($record, ['type' => 'A']);
                        self::$config['route53'][] = array_merge($record, ['type' => 'AAAA']);

                        // S3
                        if (! isset (self::$config['s3']) || ! is_array(self::$config['s3'])) {
                            self::$config['s3'] = [];
                        }

                        self::$config['s3'][] = [
                            'name' => $orchestration['name'],
                            'acl'  => 'private',
                        ];
                    }

                    //
                    // The GitHub Actions runner orchestration
                    //-----------------------------------------------------------------------------

                    if ('github-runner' === $orchestration['type']) {
                        if (! isset (self::$config['ec2_asg']) || ! is_array(self::$config['ec2_asg'])) {
                            self::$config['ec2_asg'] = [];
                        }

                        self::$config['ec2_asg'][] = [
                            'name'          => $orchestration['name'],
                            'max_size'      => $orchestration['instances'],
                            'min_size'      => $orchestration['instances'],
                            'instance_type' => $orchestration['instance_type'],
                            'spot_price'    => $orchestration['spot_price'],
                            'user_data'     => implode("\n", [
                                'mkdir /home/ec2-user/actions-runner ;',
                                'cd /home/ec2-user/actions-runner ;',
                                'wget -q https://github.com/actions/runner/releases/download/v2.169.0/actions-runner-linux-x64-2.169.0.tar.gz ;',
                                'tar xzf ./actions-runner-linux-x64-2.169.0.tar.gz ;',
                                'chown -R ec2-user:ec2-user /home/ec2-user/actions-runner ;',
                                "sudo su -- ec2-user ./config.sh --url {$orchestration['url']} --token {$orchestration['token']} --unattended ;",
                                './svc.sh install ec2-user ;',
                                './svc.sh start ;',
                            ]),
                        ];
                    }
                }
            }

            // The cloud_front template needs aliases to be joined like this
            if (isset (self::$config['cloud_front']) && is_array(self::$config['cloud_front'])) {
                foreach (self::$config['cloud_front'] as &$cloud_front) {
                    $cloud_front['joined_aliases'] = implode('","', $cloud_front['aliases']);
                }
            }

            // Te route53 template needs an array of zones
            self::$config['zones'] = [];

            if (isset(self::$config['route53']) && is_array(self::$config['route53'])) {
                foreach (self::$config['route53'] as &$route53) {
                    self::$config['zones'][] = $record['zone'];

                    // It also needs records to be joined like this
                    if (isset($route53['records']) && is_array($route53['records'])) {
                        $route53['joined_records'] = implode('","', $route53['records']);
                    }
                }

                self::$config['zones'] = array_unique(self::$config['zones']);
            }

            echo 'Using config:';
            print_r(self::$config);
        }

        return self::$config;
    }

    public static function getTerraformDir()
    {
        return getcwd().'/terraform';
    }

    public static function getTemplatesDir()
    {
        return getcwd().'/templates';
    }
}
