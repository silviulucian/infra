class String
  def resourceify
    self.gsub(/[^A-Za-z0-9\-_]/, '-')
  end
end
