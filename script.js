var username;
var scanlines = $('.scanlines');
var tv = $('.tv');

var __EVAL = (s) => eval(`void (__EVAL = ${__EVAL}); ${s}`);

var term = $('#terminal').terminal(function(command, term) {
    var cmdln = $.terminal.parse_command(command);
    var cmd = cmdln.args;
    cmd.unshift(cmdln.name);
    if (cmd.includes("+") || cmd.includes("*") || cmd.includes("/") || cmd.includes("-") || cmd.includes("%") || cmd.includes("**") || cmd.includes("//") || cmd.includes("==") || cmd.includes("!=") || cmd.includes("<") || cmd.includes(">") || cmd.includes("<=") || cmd.includes(">=") || cmd.includes("||") || cmd.includes("<<") || cmd.includes(">>") || cmd.includes("&") || cmd.includes("|") || cmd.includes("^")){
       term.echo(eval(cmd.join(" "))); 
    }
    else{
        for (let i = 0; i < cmd.length; i++) {
            if (cmd[i] != "&&"){
                if (cmd[i] === 'exit') {
                    exit();
                } else if (cmd[i] === 'echo') {
                    term.echo(cmd[i+1]);
                }else if (cmd[i] === 'whoami') {
                    term.echo("SHB");
                }else if (cmd[i] === 'cmd') {
                    rerun();
                }else if (cmd[i] === 'ls' || cmd[i] === 'l' || cmd[i] === 'dir') {
                    term.echo("index.html   script.js   style.css");
                }else if (cmd[i] === 'help') {
                    help();
                }else if (cmd[i] === 'setname') { 
                    username = cmd[i+1];
                    term.set_prompt('root@' + username + '> ');
                }else if (cmd[i] === 'cd') {
                        if (cmd[i+1].includes("https://")  || cmd[i+1].includes("http://")){
                        window.location.replace(cmd[i+1]);
                    }else {
                        window.location.replace('https://' + cmd[i+1]);
                    }
                }else if (cmd[i] === 'cat') {
                    switch (cmd[i+1]) {
                        case 'index.html':
                            gettext('index.html');
                            break;
                        case 'script.js':
                            gettext('script.js');
                            break;
                        case 'style.css':
                            gettext('style.css');
                            break;
                        default:
                            term.error('File not found');
                            break;
                    }
                }
                else if (command !== '') {
                    try {
                        var result = __EVAL(command);
                        if (result && result instanceof $.fn.init) {
                            term.echo('<#jQuery>');
                        } else if (result && typeof result === 'object') {
                            tree(result);
                        } else if (result !== undefined) {
                            term.echo(new String(result));
                        }
                    } catch(e){
                        if (new String(e).includes("Syntax")){
                            //term.echo("Type [[b;#fff;]help] to list commands");
                        }
                        else{
                            term.error(new String(e));
                        }
                    }
                }
                
            }
        }
    }
    //term.echo(n);
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

rerun();

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

function rerun() {
    term.clear();
    term.echo('\r\n  ________  __    __   _______   \r\n \/\"       )\/\" |  | \"\\ |   _  \"\\  \r\n(:   \\___\/(:  (__)  :)(. |_)  :) \r\n \\___  \\   \\\/      \\\/ |:     \\\/  \r\n  __\/  \\\\  \/\/  __  \\\\ (|  _  \\\\  \r\n \/\" \\   :)(:  (  )  :)|: |_)  :) \r\n(_______\/  \\__|  |__\/ (_______\/  \r\n      \r\n');
    term.echo('Type [[b;#fff;]help] to list commands');
}

function exit() {
    $('.tv').addClass('collapse');
    term.disable();
    setTimeout(function() {
    window.close()
    }, 2000);
}

function help() {
    term.echo("Type [[b;#fff;]setname] [[b;#f00;]name] to set username");
    term.echo("Type [[b;#fff;]cd] [[b;#f00;]site] to redirect to site");
    term.echo("Type [[b;#fff;]cat] [[b;#f00;]file] to view file");
    term.echo("Type [[b;#fff;]echo] [[b;#f00;]text] to echo text");
    term.echo("Type [[b;#fff;]whoami] to see author");
    term.echo("Type [[b;#fff;]clear] to clear terminal");
    term.echo("Type [[b;#fff;]cmd] to rerun terminal");
    term.echo("Type [[b;#fff;]ls]/[[b;#fff;]dir] to list files"); 
    term.echo("Type [[b;#fff;]help] to list commands");
    term.echo("Type [[b;#fff;]exit] to exit site");
}

function gettext(file){
    fetch(file)
        .then(function(response) {
            return response.text();
        })
        .then(function(data) {
            term.echo(data);
        })
        .catch(function(error) {
            term.error(error);
        });
}
