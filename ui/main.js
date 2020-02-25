//
// This is the main app for the ntst module
//
function qruqsp_ntst_main() {
    // The flags for each participant
    this.participantFlags = {
        '1':{'name':'Net Control'},
        '2':{'name':'Send'},
        '3':{'name':'Receive'},
        };
    //
    // The panel to list the net
    //
    this.menu = new M.panel('Training Net', 'qruqsp_ntst_main', 'menu', 'mc', 'medium', 'sectioned', 'qruqsp.ntst.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
            'cellClasses':[''],
            'hint':'Search net',
            'noData':'No net found',
            },
        'nets':{'label':'Net', 'type':'simplegrid', 'num_cols':3,
            'headerValues':['Name', 'Start Date', 'Status'],
            'noData':'No net',
            'addTxt':'Add Net',
            'addFn':'M.qruqsp_ntst_main.edit.open(\'M.qruqsp_ntst_main.menu.open();\',0,null);'
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('qruqsp.ntst.netSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.qruqsp_ntst_main.menu.liveSearchShow('search',null,M.gE(M.qruqsp_ntst_main.menu.panelUID + '_' + s), rsp.nets);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.qruqsp_ntst_main.net.open(\'M.qruqsp_ntst_main.menu.open();\',\'' + d.id + '\');';
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'nets' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.start_date_text;
                case 2: return d.status_text;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'nets' ) {
            return 'M.qruqsp_ntst_main.net.open(\'M.qruqsp_ntst_main.menu.open();\',\'' + d.id + '\',M.qruqsp_ntst_main.net.nplist);';
        }
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('qruqsp.ntst.netList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_ntst_main.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The panel to display Net
    //
    this.net = new M.panel('Net', 'qruqsp_ntst_main', 'net', 'mc', 'large mediumaside', 'sectioned', 'qruqsp.ntst.main.net');
    this.net.data = null;
    this.net.net_id = 0;
    this.net.searchData = [];
    this.net.sections = {
        'details':{'label':'Net', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.qruqsp_ntst_main.edit.open(\'M.qruqsp_ntst_main.net.open();\',M.qruqsp_ntst_main.net.net_id);',
            },
        'add':{'label':'Add Participant', 'aside':'yes', 'fields':{
            'callsign':{'label':'Callsign', 'autofocus':'yes', 'livesearch':'yes', 'required':'yes', 'type':'text', 
//                'onkeyupFn':'M.qruqsp_ntst_main.net.keyup();',
//                'enterFn':'M.qruqsp_ntst_main.net.switchFocus(\'name\');',
//                'tabFn':'M.qruqsp_ntst_main.net.switchFocus(\'name\');',
                'livesearch':'yes',
                },
            'name':{'label':'Name', 'type':'text',
//                'onkeyupFn':'M.qruqsp_ntst_main.net.keyup();',
//                'enterFn':'M.qruqsp_ntst_main.net.switchFocus(\'email\');',
//                'tabFn':'M.qruqsp_ntst_main.net.switchFocus(\'email\');',
                },
            'flags':{'label':'Options', 'type':'flags', 'flags':this.participantFlags},
            'place_of_origin':{'label':'Origin', 'hint':'Place of Origin', 'type':'text', },
            'address':{'label':'Address', 'type':'textarea', 'size':'small'},
            'phone':{'label':'Phone', 'type':'text', },
            'email':{'label':'Email', 'type':'text',
//                'onkeyupFn':'M.qruqsp_ntst_main.net.keyup();',
//                'enterFn':'M.qruqsp_ntst_main.net.addParticipant();',
//                'tabFn':'M.qruqsp_ntst_main.net.switchFocus(\'callsign\');',
                },
            }},
        '_addbuttons':{'label':'', 'aside':'yes', 'buttons':{
            'add':{'label':'Add Participant', 'fn':'M.qruqsp_ntst_main.net.addParticipant();'},
            }},
        'participants':{'label':'Participants', 'type':'simplegrid', 'num_cols':4,
            'cellClasses':['multiline', 'multiline', 'multiline', 'alignright'],
            'headerValues':['Callsign', 'Email', 'Options', ''],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', ''],
            'noData':'No callsigns added',
            },
        'messages':{'label':'Messages', 'type':'simplegrid', 'num_cols':5,
            'cellClasses':['multiline aligntop', 'aligntop', 'multiline aligntop', 'multiline aligntop', 'alignright'],
            'headerValues':['Callsign', 'Number', 'To', 'Message', ''],
            'sortable':'yes',
            'sortTypes':['text', 'number', 'text', ''],
            'noData':'No messages added',
            },
    }
    this.net.fieldValue = function(s, i, d) {
        return '';
    }
    this.net.liveSearchCb = function(s, i, value) {
        M.api.getJSONBgCb('qruqsp.ntst.participantSearch', {'tnid':M.curTenantID, 'start_needle':value, 'limit':25}, function(rsp) {
            M.qruqsp_ntst_main.net.searchData = rsp.participants;
            M.qruqsp_ntst_main.net.liveSearchShow(s, i, M.gE(M.qruqsp_ntst_main.net.panelUID + '_' + i), rsp.participants);
});
    }
    this.net.liveSearchResultValue = function(s, f, i, j, d) {
        return d.callsign + ' - ' + d.name;
    }
    this.net.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.qruqsp_ntst_main.net.fillForm(i);';
    }
    this.net.fillForm = function(idx) {
        for(var i in this.sections.add.fields) {
            this.setFieldValue(i, this.searchData[idx][i]);
        }
    }
    this.net.cellValue = function(s, i, j, d) {
        if( s == 'details' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
            }
        }
        if( s == 'participants' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.callsign + ' - ' + d.name + '</span><span class="subtext">' + d.place_of_origin + '</span>';
                case 1: return '<span class="maintext">' + d.email + '</span><span class="subtext">' + d.address.replace(/\n/, ', ') + '</span>';
                case 2: return '<span class="maintext">' + d.options + '</span><span class="subtext">' + d.phone + '</span>';
                case 3: return (d.flags&0x02) == 0x02 ? '<button onclick="event.stopPropagation(); M.qruqsp_ntst_main.net.newMessage(' + d.id + ');">New Message</button>' : '';
            }
        }
        if( s == 'messages' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.callsign + '</span><span class="subtext">' + d.place_of_origin + '</span>';
                case 1: return d.number;
                case 2: return '<span class="maintext">' + d.to_name_address.replace(/\n/, '<br/>') + '</span><span class="subtext">' + d.phone_number + ' ' + d.email + '</span>';
                case 3: return '<span class="maintext">' + d.message + '</span><span class="subtext">' + d.signature + '</span>';
                case 4: return '<button onclick="M.qruqsp_ntst_main.net.resentMessage(' + d.id + ');">Resend</button>';
            }
        }
    }
    this.net.rowFn = function(s, i, d) {
        if( s == 'participants' ) {
            return 'M.qruqsp_ntst_main.participant.open(\'M.qruqsp_ntst_main.net.open();\',\'' + d.id + '\');';
        }
        if( s == 'messages' ) {
            return 'M.qruqsp_ntst_main.message.open(\'M.qruqsp_ntst_main.net.open();\',\'' + d.id + '\');';
        }
        return '';
    }
    this.net.switchFocus = function(f) {
        var e = M.gE(this.panelUID + '_' + f);
        e.focus();
    }
    this.net.keyup = function() {
        console.log('testing');
        return false;
    }
    this.net.newMessage = function(pid) {
        M.qruqsp_ntst_main.message.open('M.qruqsp_ntst_main.net.open();', 0, pid);
/*        M.api.getJSONCb('qruqsp.ntst.messageAdd', {'tnid':M.curTenantID, 'net_id':this.net_id, 'participant_id':pid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_ntst_main.net;
            p.data.messages = rsp.net.messages;
            p.refreshSection('messages');
        }); */
    }
    this.net.resendMessage = function(mid) {
        M.api.getJSONCb('qruqsp.ntst.messageResend', {'tnid':M.curTenantID, 'net_id':this.net_id, 'message_id':mid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_ntst_main.net;
            p.data.messages = rsp.net.messages;
            p.refreshSection('messages');
        });
    }
    this.net.addSearchParticipant = function(callsign, name, email) {
        var c = '&callsign=' + M.eU(unescape(callsign))
            + '&name=' + M.eU(unescape(name))
            + '&email=' + M.eU(unescape(email));
        M.api.postJSONCb('qruqsp.ntst.participantAdd', {'tnid':M.curTenantID, 'net_id':this.net_id}, c, this.openFinish);
    }
    this.net.addParticipant = function() {
        if( !this.checkForm() ) {
            return false;
        }
        var c = this.serializeForm('yes');
        M.api.postJSONCb('qruqsp.ntst.participantAdd', {'tnid':M.curTenantID, 'net_id':this.net_id}, c, this.openFinish);
    }
/*    this.net.removeParticipant = function(i) {
        if( confirm('Are you sure you want to remove this participant?') ) {
            M.api.getJSONCb('qruqsp.ntst.participantDelete', {'tnid':M.curTenantID, 'net_id':this.net_id, 'participant_id':i}, this.openFinish);
        }
    } */
    this.net.open = function(cb, nid, list) {
        if( nid != null ) { this.net_id = nid; }
        if( list != null ) { this.nplist = list; }
        if( cb != null ) { this.cb = cb; }
        M.api.getJSONCb('qruqsp.ntst.netGet', {'tnid':M.curTenantID, 'net_id':this.net_id}, this.openFinish);
    }
    this.net.openFinish = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.qruqsp_ntst_main.net;
        p.data = rsp.net;
        p.data.details = [
            {'label':'Name', 'value':rsp.net.name},
            {'label':'Status', 'value':rsp.net.status_text},
            {'label':'Start', 'value':rsp.net.start_utc_date + ' ' + rsp.net.start_utc_time},
            {'label':'End', 'value':rsp.net.end_utc_date + ' ' + rsp.net.end_utc_time},
            ];
        p.refresh();
        p.show();
    }
    this.net.addButton('edit', 'Edit', 'M.qruqsp_ntst_main.edit.open(\'M.qruqsp_ntst_main.net.open();\',M.qruqsp_ntst_main.net.net_id);');
    this.net.addClose('Back');

    //
    // The panel to edit Net
    //
    this.edit = new M.panel('Net', 'qruqsp_ntst_main', 'edit', 'mc', 'medium', 'sectioned', 'qruqsp.ntst.main.edit');
    this.edit.data = null;
    this.edit.net_id = 0;
    this.edit.nplist = [];
    this.edit.sections = {
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Pending', '50':'Active', '90':'Closed'}},
            'start_utc_date':{'label':'Start Date', 'required':'yes', 'type':'date', 'hint':'UTC'},
            'start_utc_time':{'label':'Start Time', 'required':'yes', 'type':'text', 'size':'small'},
            'end_utc_date':{'label':'End Date', 'required':'yes', 'type':'date', 'hint':'UTC'},
            'end_utc_time':{'label':'End Time', 'required':'yes', 'type':'text', 'size':'small'},
//            }},
//        'messages':{'label':'Message Files', 'fields':{
            'message_sources':{'label':'Messages', 'type':'toggle', 'none':'yes', 'toggles':{'jokes':'Jokes', 'quotes':'Quotes', 'samples':'Sample Messages'}},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.qruqsp_ntst_main.edit.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_ntst_main.edit.net_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_ntst_main.edit.remove();'},
            }},
        };
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.ntst.netHistory', 'args':{'tnid':M.curTenantID, 'net_id':this.net_id, 'field':i}};
    }
    this.edit.open = function(cb, nid, list) {
        if( nid != null ) { this.net_id = nid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('qruqsp.ntst.netGet', {'tnid':M.curTenantID, 'net_id':this.net_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_ntst_main.edit;
            p.data = rsp.net;
            p.refresh();
            p.show(cb);
        });
    }
    this.edit.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_ntst_main.edit.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.net_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('qruqsp.ntst.netUpdate', {'tnid':M.curTenantID, 'net_id':this.net_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('qruqsp.ntst.netAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_ntst_main.edit.net_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.edit.remove = function() {
        if( confirm('Are you sure you want to remove net?') ) {
            M.api.getJSONCb('qruqsp.ntst.netDelete', {'tnid':M.curTenantID, 'net_id':this.net_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_ntst_main.edit.close();
            });
        }
    }
    this.edit.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.net_id) < (this.nplist.length - 1) ) {
            return 'M.qruqsp_ntst_main.edit.save(\'M.qruqsp_ntst_main.edit.open(null,' + this.nplist[this.nplist.indexOf('' + this.net_id) + 1] + ');\');';
        }
        return null;
    }
    this.edit.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.net_id) > 0 ) {
            return 'M.qruqsp_ntst_main.edit.save(\'M.qruqsp_ntst_main.edit.open(null,' + this.nplist[this.nplist.indexOf('' + this.net_id) - 1] + ');\');';
        }
        return null;
    }
    this.edit.addButton('save', 'Save', 'M.qruqsp_ntst_main.edit.save();');
    this.edit.addClose('Cancel');
    this.edit.addButton('next', 'Next');
    this.edit.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Participant
    //
    this.participant = new M.panel('Participant', 'qruqsp_ntst_main', 'participant', 'mc', 'medium', 'sectioned', 'qruqsp.ntst.main.participant');
    this.participant.data = null;
    this.participant.participant_id = 0;
    this.participant.nplist = [];
    this.participant.sections = {
        'general':{'label':'', 'fields':{
            'callsign':{'label':'Callsign', 'required':'yes', 'type':'text'},
            'flags':{'label':'Options', 'type':'flags', 'flags':this.participantFlags},
            'name':{'label':'Name', 'type':'text'},
            'place_of_origin':{'label':'Place of Origin', 'type':'text'},
            'address':{'label':'Address', 'type':'textarea', 'size':'medium'},
            'phone':{'label':'Phone', 'type':'text'},
            'email':{'label':'Email', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.qruqsp_ntst_main.participant.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_ntst_main.participant.participant_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_ntst_main.participant.remove();'},
            }},
        };
    this.participant.fieldValue = function(s, i, d) { return this.data[i]; }
    this.participant.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.ntst.participantHistory', 'args':{'tnid':M.curTenantID, 'participant_id':this.participant_id, 'field':i}};
    }
    this.participant.open = function(cb, pid, list) {
        if( pid != null ) { this.participant_id = pid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('qruqsp.ntst.participantGet', {'tnid':M.curTenantID, 'participant_id':this.participant_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_ntst_main.participant;
            p.data = rsp.participant;
            p.refresh();
            p.show(cb);
        });
    }
    this.participant.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_ntst_main.participant.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.participant_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('qruqsp.ntst.participantUpdate', {'tnid':M.curTenantID, 'participant_id':this.participant_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('qruqsp.ntst.participantAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_ntst_main.participant.participant_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.participant.remove = function() {
        if( confirm('Are you sure you want to remove participant?') ) {
            M.api.getJSONCb('qruqsp.ntst.participantDelete', {'tnid':M.curTenantID, 'participant_id':this.participant_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_ntst_main.participant.close();
            });
        }
    }
    this.participant.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.participant_id) < (this.nplist.length - 1) ) {
            return 'M.qruqsp_ntst_main.participant.save(\'M.qruqsp_ntst_main.participant.open(null,' + this.nplist[this.nplist.indexOf('' + this.participant_id) + 1] + ');\');';
        }
        return null;
    }
    this.participant.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.participant_id) > 0 ) {
            return 'M.qruqsp_ntst_main.participant.save(\'M.qruqsp_ntst_main.participant.open(null,' + this.nplist[this.nplist.indexOf('' + this.participant_id) - 1] + ');\');';
        }
        return null;
    }
    this.participant.addButton('save', 'Save', 'M.qruqsp_ntst_main.participant.save();');
    this.participant.addClose('Cancel');
    this.participant.addButton('next', 'Next');
    this.participant.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Message
    //
    this.message = new M.panel('Message', 'qruqsp_ntst_main', 'message', 'mc', 'medium mediumaside', 'sectioned', 'qruqsp.ntst.main.message');
    this.message.data = null;
    this.message.message_id = 0;
    this.message.participant_id = 0;
    this.message.nplist = [];
    this.message.sections = {
        'general':{'label':'From', 'aside':'yes', 'fields':{
//            'participant_id':{'label':'Sender', 'required':'yes', 'type':'select', 'editable':'no',
//                'complex_options':{'value':'id', 'name':'name'},
//                'options':{}},
            'participant_name':{'label':'Sender', 'type':'text', 'editable':'no'},
            'number':{'label':'Message Number', 'type':'text', 'size':'small'},
            'precedence':{'label':'Precedence', 'type':'text', 'size':'small'},
            'hx':{'label':'Handling', 'type':'text', 'size':'small'},
            'station_of_origin':{'label':'Station of Origin', 'type':'text', 'size':'small', 'editable':'no'},
            'check_number':{'label':'Check', 'type':'text', 'size':'small'},
            'place_of_origin':{'label':'Place of Origin', 'type':'text', 'size':'small'},
            'time_filed':{'label':'Time Filed', 'type':'text', 'size':'small'},
            'date_filed':{'label':'Date Filed', 'type':'text', 'size':'small'},
            }},
        '_to':{'label':'To', 'fields':{
            'to_name_address':{'label':'Name/Address', 'type':'textarea', 'size':'medium'},
            'phone_number':{'label':'Phone Number', 'type':'text'},
            'email':{'label':'Email', 'type':'text'},
            }},
        '_message':{'label':'', 'fields':{
            'message':{'label':'Message', 'type':'textarea'},
            'spoken':{'label':'Spoken', 'type':'textarea'},
            'signature':{'label':'Signature', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Send', 'fn':'M.qruqsp_ntst_main.message.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_ntst_main.message.message_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_ntst_main.message.remove();'},
            }},
        };
    this.message.fieldValue = function(s, i, d) { return this.data[i]; }
    this.message.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.ntst.messageHistory', 'args':{'tnid':M.curTenantID, 'message_id':this.message_id, 'field':i}};
    }
    this.message.open = function(cb, mid, pid, list) {
        if( mid != null ) { this.message_id = mid; }
        if( pid != null ) { this.participant_id = pid; }
        if( list != null ) { this.nplist = list; }
        var args = {'tnid':M.curTenantID, 'message_id':this.message_id};
        if( pid != null && pid != '' ) {
            args['participant_id'] = pid;
        }
        M.api.getJSONCb('qruqsp.ntst.messageGet', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_ntst_main.message;
            p.data = rsp.message;
            //p.sections.general.fields.participant_id.options = rsp.participants;
            p.refresh();
            p.show(cb);
        });
    }
    this.message.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_ntst_main.message.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.message_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('qruqsp.ntst.messageUpdate', {'tnid':M.curTenantID, 'message_id':this.message_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('qruqsp.ntst.messageAdd', {'tnid':M.curTenantID, 'participant_id':this.participant_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_ntst_main.message.message_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.message.remove = function() {
        if( confirm('Are you sure you want to remove message?') ) {
            M.api.getJSONCb('qruqsp.ntst.messageDelete', {'tnid':M.curTenantID, 'message_id':this.message_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_ntst_main.message.close();
            });
        }
    }
    this.message.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.message_id) < (this.nplist.length - 1) ) {
            return 'M.qruqsp_ntst_main.message.save(\'M.qruqsp_ntst_main.message.open(null,' + this.nplist[this.nplist.indexOf('' + this.message_id) + 1] + ');\');';
        }
        return null;
    }
    this.message.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.message_id) > 0 ) {
            return 'M.qruqsp_ntst_main.message.save(\'M.qruqsp_ntst_main.message.open(null,' + this.nplist[this.nplist.indexOf('' + this.message_id) - 1] + ');\');';
        }
        return null;
    }
    this.message.addButton('save', 'Save', 'M.qruqsp_ntst_main.message.save();');
    this.message.addClose('Cancel');
    this.message.addButton('next', 'Next');
    this.message.addLeftButton('prev', 'Prev');

    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }
        
        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'qruqsp_ntst_main', 'yes');
        if( ac == null ) {
            alert('App Error');
            return false;
        }
        
        this.menu.open(cb);
    }
}
