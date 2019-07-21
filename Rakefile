# frozen_string_literal: true

require 'erb'
require 'yaml'
require './template_helpers.rb'

#
# Config
###############################################################################

CONFIG = YAML.load_file('config.yml')
TEMPLATE_DIR = 'templates'
TERRAFORM_DIR = 'templates/.tf'
TEMPLATE_FILES = Rake::FileList.new("#{TEMPLATE_DIR}/**/*.tf.erb") do |tf|
  # Files to exclude
  [
    '~*',
    %r{^somefolder/}
  ].each { |excl| tf.exclude(excl) }

  # Exclude files that aren't tracked by git
  # tf.exclude do |f|
  #   `git ls-files #{f}`.empty?
  # end
end

#
# Don't edit beyond this points
###############################################################################

#
# Returns a template file for a given erb file, ignoring file extensions

def template_for_tf(tf_file)
  TEMPLATE_FILES.detect { |tpl_file| tpl_file.ext('') == tf_file.pathmap("%{^#{TERRAFORM_DIR}/,#{TEMPLATE_DIR}/}X") }
end

#
# Ensure terraform directory is there

directory TERRAFORM_DIR

#
# Tasks

# Turn erb templates into terraform files
rule '.tf' => [->(f) { template_for_tf(f) }, TERRAFORM_DIR] do |t|
  dest_dir = t.name.pathmap('%d')
  mkdir_p dest_dir unless File.exist?(dest_dir)

  dest_data = ERB.new(File.read(t.source)).result
  File.open(t.name, 'w+') { |file| file.write(dest_data) }

  # Reinitialize if backend file changes
  Dir.chdir(TERRAFORM_DIR) { sh 'terraform init' } if /backend\.tf/ =~ t.name
end

task tf: TEMPLATE_FILES.pathmap("%{^#{TEMPLATE_DIR}/,#{TERRAFORM_DIR}/}X.tf")

task :fmt do
  Dir.chdir(TERRAFORM_DIR) { sh 'pwd && terraform fmt' }
end

task :apply do
  Dir.chdir(TERRAFORM_DIR) { sh 'terraform apply' }
end

task :clean do
  rm_rf TERRAFORM_DIR
end

task default: %i[clean tf fmt apply]

task destroy: %i[clean tf fmt] do
  Dir.chdir(TERRAFORM_DIR) { sh 'terraform destroy' }
end
