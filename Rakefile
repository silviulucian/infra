require 'erb'
require 'yaml'
require './template_helpers.rb'

#
# Config and vars

CONFIG         = YAML.load_file("config.yml")
TEMPLATE_DIR   = "templates"
TERRAFORM_DIR  = "templates/.tf"
TEMPLATE_FILES = Rake::FileList.new("#{TEMPLATE_DIR}/**/*.tferb") do |tf|
  # Files to exclude
  [
    "~*",
    /^somefolder\//
  ].each { |excl| tf.exclude(excl) }

  # Exclude files that aren't tracked by git
  # tf.exclude do |f|
  #   `git ls-files #{f}`.empty?
  # end
end

#
# Returns a template file for a given erb file ignoring file extensions

def template_for_erb(json_file)
  TEMPLATE_FILES.detect { |tf| tf.ext("") == json_file.pathmap("%{^#{TERRAFORM_DIR}/,#{TEMPLATE_DIR}/}X") }
end

#
# Ensure terraform directory is there

directory "#{TERRAFORM_DIR}"

#
# Turn erb templates into terraform files

rule ".tf" => [->(f){ template_for_erb(f) }, "#{TERRAFORM_DIR}"] do |t|
  dest_dir = t.name.pathmap("%d")
  mkdir_p dest_dir if !File.exist?(dest_dir)

  dest_data = ERB.new(File.read(t.source)).result
  File.open(t.name, 'w+') { |file| file.write(dest_data) }

  # Reinitialize if backend file changes

  Dir.chdir(TERRAFORM_DIR) { sh 'terraform init' } if /backend\.tf/ =~ t.name
end

task :tf => TEMPLATE_FILES.pathmap("%{^#{TEMPLATE_DIR}/,#{TERRAFORM_DIR}/}X.tf")

#
# Terraform format and apply tasks

task :fmt do
  Dir.chdir(TERRAFORM_DIR) { sh 'terraform fmt' }
end

task :apply do
  Dir.chdir(TERRAFORM_DIR) { sh 'terraform apply' }
end

#
# Cleanup task

task :clean do
  rm_rf "#{TERRAFORM_DIR}"
end

#
# Default task

task :default => [ :clean, :tf, :fmt, :apply ]

#
# Destroy task
task :destroy => [:clean, :tf, :fmt] do
  Dir.chdir(TERRAFORM_DIR) { sh 'terraform destroy' }
end