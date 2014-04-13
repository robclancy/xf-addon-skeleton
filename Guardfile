require 'guard/plugin'
require 'active_support'
require 'active_support/core_ext/string/inflections'

# compile .slim files in "templates" into "generated/templates"
guard 'slim', :slim => { :pretty => true }, :output_root => 'generated' do
  watch(%r'^templates/.+\.slim$')
end

# need to make any deleted slim file also deletes the generated file
module ::Guard
  class SlimRemove < ::Guard::Plugin
    def run_on_removals(paths)
      paths.each {|p| File.delete 'generated/'+p.sub('.slim', '')}
    end
  end
end

guard 'slim-remove' do
  watch(%r'^templates/.+\.slim$')
end

# pre compile scss files to so that xenforo syntax gets through the sass process
# basically escapes/stringifys things
module ::Guard
  class XenForoSassPreCompile < ::Guard::Plugin
    def run_on_changes(paths)
      comment_wrap = ['/*!', '!*/']
      string_wrap = ['"!!', '!!"']
      media_wrap = ['@media(max-width: 1337){ __property {content: \'', '\';}']
      
      paths.each do |path|
        sass_file = File.open path
        sass = sass_file.read
        sass_file.close
        
        # comment the properties in syntax like...
        #  @property "html";
        #  color: red;
        #  @property "/html";
        sass.gsub!(/@property(["a-z0-9 \/]*);/i, comment_wrap[0]+'@property\1;'+comment_wrap[1])
        
        # comment out <xen:* /> syntax
        sass.gsub!(/((<xen:|<\/xen:)[a-z0-9@_ \-\.="'\/]*>)/i, comment_wrap[0]+'\1'+comment_wrap[1])
        
        # comment {xen: method, arg, arg2} syntax and add string after it
        sass.gsub!(/(\{xen:[a-z0-9_ \-\.="'@+,*\/]*})/i, comment_wrap[0]+'\1'+comment_wrap[1]+string_wrap[0]+string_wrap[1])
        
        # @media (max-width something) needs a custom replace due to compiler being a lil bitch
        sass.gsub!(/(@media[a-z0-9_ \-\.="'@+,*\{\}\(\):\!*\/]*){[ ]*\n/i, media_wrap[0]+'\1'+media_wrap[1]); # { same line
        
        # xenforo variables start with @, turn them into strings
        sass.gsub!(/@(?!media|keyframes|font-face|import)([a-z\.\-]*)/i, string_wrap[0]+'@\1'+string_wrap[1])
        
        # write the file
        new_path = File.join('generated/sass', path)
        FileUtils.mkdir_p File.dirname(new_path)
        File.open(new_path, 'w') {|f| f.write(sass) }
        
        UI.info "XenForoSass: Pre-compiled #{path} to #{new_path}" 
      end
    end
    
    def run_on_removals(paths)
      # just go straight to the final result and delete it, will need to work better when moved to gems
      paths.each {|p| File.delete 'generated/'+p.sub('.scss', '')}
    end
  end
end

guard 'xenforo-sass-pre-compile' do
  watch(%r'^templates/.+\.scss')
end

# compiles sass that has been xenforo escaped in "generated/sass" into "generated/css"
guard 'sass', :input => 'generated/sass', :output => 'generated/css', :style => :expanded

# post compile css files to add back in the xenforo syntax properly
# basically unescapes/unstringifys things
module ::Guard
  class XenForoSassPostCompile < ::Guard::Plugin
    def run_on_changes(paths)
      comment_wrap = ['/*!', '!*/']
      string_wrap = ['"!!', '!!"']
      media_wrap = ["@media (max-width: 1337) {
  __property {
    content: '", "';
  }"]
      
      paths.each do |path|
        css_file = File.open path
        css = css_file.read
        css_file.close
        
        css.gsub!(Regexp.new(Regexp.quote(comment_wrap[0])), '')
        css.gsub!(Regexp.new(Regexp.quote(comment_wrap[1])), '')
        css.gsub!(Regexp.new(Regexp.quote(string_wrap[0])), '')
        css.gsub!(Regexp.new(Regexp.quote(string_wrap[1])), '')
        css.gsub!(Regexp.new(Regexp.quote(media_wrap[0])), '')
        css.gsub!(Regexp.new(Regexp.quote(media_wrap[0].gsub("'", '"'))), '')
        css.gsub!(Regexp.new(Regexp.quote(media_wrap[1])), '{')
        
        # write the file
        new_path = File.join('generated/templates', path.sub('generated/css/templates/', ''))
        FileUtils.mkdir_p File.dirname(new_path)
        File.open(new_path, 'w') {|f| f.write(css) }
        
        UI.info "XenForoSass: Post-compiled #{path} to #{new_path}" 
      end
    end
  end
end

guard 'xenforo-sass-post-compile' do
  watch(%r'^generated/css/.+\.css')
end

# changes to "generated/templates" files are to be sent to xenforo
module ::Guard  
  class XenForoTemplate < ::Guard::Plugin
    def run_on_additions(paths)
      paths.each do |path|
        t = get_template_details path
        
        spawn "php vendor/robclancy/xf-toolkit/src/xf.php template:create #{t[:name]}#{t[:addon_id].empty? ? '' : ' --addon-id='+t[:addon_id]}#{t[:admin] ? ' --admin' : ''} --update-if-exists --file=#{path}"
      end
    end

    def run_on_modifications(paths)
      paths.each do |path|
        t = get_template_details path
        spawn "php vendor/robclancy/xf-toolkit/src/xf.php  template:update #{t[:name]}#{t[:addon_id].empty? ? '' : ' --addon-id='+t[:addon_id]}#{t[:admin] ? ' --admin' : ''} --create-if-not-exists --file=#{path}"
      end
    end
    
    def run_on_removals(paths)
      paths.each do |path|
        t = get_template_details path
        
        spawn "php vendor/robclancy/xf-toolkit/src/xf.php  template:delete #{t[:name]}#{t[:admin] ? ' --admin' : ''}"
      end
    end
    
    private
    
    def get_template_details(path)
      path = path.sub 'generated/templates/', ''
      
      addon_id = path.include?('/') ? path.split('/').first : ''
      addon_id = '' if addon_id == 'admin'
      name = path.gsub('/', '_').sub('.html', '').underscore
      
      if addon_id.empty?
        admin = name.start_with? 'admin'
        name.sub! 'admin_', '' if admin
      else
        admin = name.start_with? addon_id.underscore+'_admin'
        name.sub! '_admin', '' if admin
      end
      
      {
        addon_id: addon_id,
        admin: admin,
        name: name
      }
    end
  end
end

guard 'xenforo-template' do
  watch(%r'^generated/templates/.+')
end

