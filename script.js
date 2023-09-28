var username;
var scanlines = $('.scanlines');
var tv = $('.tv');

var __EVAL = (s) => eval(`void (__EVAL = ${__EVAL}); ${s}`);

var term = $('#terminal').terminal(function(command, term) {
    var cmd = $.terminal.parse_command(command);
    if (cmd.name === 'exit') {
        exit();
    } else if (cmd.name === 'echo') {
        term.echo(cmd.rest);
    }else if (cmd.name === 'whoami') {
        term.echo("SHB");
    }else if (cmd.name === 'clear' || cmd.name === 'cls') {
        clear();
    }else if (cmd.name === 'ls' || cmd.name === 'dir') {
        term.echo("index.html   script.js   style.css");
    }else if (cmd.name === 'help') {
        term.echo("echo [text]   whoami   clear/cls   ls/dir   help   setname [name] exit");
    }else if (cmd.name === 'setname') { 
        username = cmd.rest;
        term.set_prompt('root@' + username + '> ');
    }else if (cmd.name === 'getos') {
        term.echo(getOS());
    }else if (command !== '') {
        try {
            var result = __EVAL(command);
            if (result && result instanceof $.fn.init) {
                term.echo('<#jQuery>');
            } else if (result && typeof result === 'object') {
                tree(result);
            } else if (result !== undefined) {
                term.echo(new String(result));
            }
        } catch(e) {
            term.error(new String(e));
        }

    }
}, {
    name: 'shell',
    onResize: set_size,
    exit: false,
    enabled: $('body').attr('onload') === undefined,
    onInit: function() {
        set_size();       
    },
    prompt: 'root@null> '
});

clear();

if (!term.enabled()) {
    term.find('.cursor').addClass('blink');
}
function set_size() {
    var height = $(window).height();
    var width = $(window).width()
    var time = (height * 2) / 170;
    scanlines[0].style.setProperty("--time", time);
    tv[0].style.setProperty("--width", width);
    tv[0].style.setProperty("--height", height);
}

function tree(obj) {
    term.echo(treeify.asTree(obj, true, true));
}

function clear() {
    term.clear();
    term.echo('\r\n  ________  __    __   _______   \r\n \/\"       )\/\" |  | \"\\ |   _  \"\\  \r\n(:   \\___\/(:  (__)  :)(. |_)  :) \r\n \\___  \\   \\\/      \\\/ |:     \\\/  \r\n  __\/  \\\\  \/\/  __  \\\\ (|  _  \\\\  \r\n \/\" \\   :)(:  (  )  :)|: |_)  :) \r\n(_______\/  \\__|  |__\/ (_______\/  \r\n      \r\n');
    term.echo('Type [[b;#fff;]help] to list commands');
}

function exit() {
    $('.tv').addClass('collapse');
    term.disable();
    setTimeout(function() {
    close()
    }, 2000);
}

const getOS = () => {
    const userAgent = window.navigator.userAgent;
    const platform = window.navigator.platform;
    const macosPlatforms = ['Macintosh', 'MacIntel', 'MacPPC', 'Mac68K'];
    const windowsPlatforms = ['Win32', 'Win64', 'Windows', 'WinCE'];
    const iosPlatforms = ['iPhone', 'iPad', 'iPod'];
    
    if (macosPlatforms.indexOf(platform) !== -1) {
      return 'Mac OS';
    } else if (iosPlatforms.indexOf(platform) !== -1) {
      return 'iOS';
    } else if (windowsPlatforms.indexOf(platform) !== -1) {
      return 'Windows';
    } else if (/Android/.test(userAgent)) {
      return 'Android';
    } else if (/Linux/.test(platform)) {
      return 'Linux';
    }
    
    return 'Unknown OS';
  };