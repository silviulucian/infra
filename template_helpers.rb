# frozen_string_literal: true

# Extend the String class
class String
  # Turn a string into a valid Terraform resource name
  def resourceify
    gsub(/[^A-Za-z0-9\-_]/, '-')
  end
end
