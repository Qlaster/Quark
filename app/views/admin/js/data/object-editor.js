const urlParams = new URLSearchParams(window.location.search);

const jstree_config = {
    node_base_name: null // оставить только числа и увеличивать их при создании новых элементов
    // node_base_name: 'new_element' // добавлять постфикс в формате new_element_N
};

const jstree_icons = {
    node_root: 'fa fa-stop',
    node_default: 'fa fa-archive',
    node_preview: 'fa fa-angle-right'
};

const property_types = {
    'head': {
        'type_name': 'head',
        'value_placeholder': 'Введите заголовок' 
    },
    'link': {
        'type_name': 'link',
        'value_placeholder': 'Введите ссылку' 
    },
    'icon': {
        'type_name': 'icon',
        'value_placeholder': 'Введите название иконки (fa-icon или др. библиотеки)' 
    },
    'info': {
        'type_name': 'info',
        'value_placeholder': 'Введите сопроводительную информацию' 
    },
    'text': {
        'type_name': 'text',
        'value_placeholder': 'Введите текстовое содержимое' 
    },
    'image': {
        'type_name': 'image',
        'value_placeholder': 'Введите ссылку на изображение' 
    },
    'blank': {
        'type_name': '',
        'value_placeholder': 'Введите значение свойства' 
    }
};

$(document).ready(function() {
    let json_data = url_get(window.location.pathname + "/../get?collection=" + encodeURIComponent(urlParams.get("collection")) + "&object=" + encodeURIComponent(urlParams.get("object")));
    jstreeInit(json_data);

    $('.js-tree-add-node').on('click', function() {
        treeAddNode();
    });
    
    $('.js-save-collection').on('click', async function() {
        if($('.js-tree-errors.visible, .js-table-errors.visible, .js-tree-warnings.visible').length) {
            toastr.error('Сохранение недоступно', 'Ошибка в структуре объекта');
            return;
        }

        const $btn = $(this);
        $btn.addClass('disabled').text('Сохранение...');

        try {
            await saveObject('admin/constructor/object/set?collection=' + encodeURIComponent(urlParams.get("collection")) + 
                                                         '&objectname=' + encodeURIComponent($('#objectname').val())
            );
        } catch (error) {
            console.error('Ошибка при сохранении:', error);
        } finally {
            $btn.removeClass('disabled').text('Сохранить объект');
        }
    });

    $('.js-properties-table-add-item').on('click', function() {
        propertiesTableAddItem($(this).data('item-type'));
    });

    // $(function () { $('#object-tree').jstree({
    //     "core" : {
    //         "check_callback" : true,
    //         "multiple" : false, // no multiselection
    //         "themes" : {
    //             "name" : "default",
    //             "variant" : "large",
    //             // "stripes" : true,
    //             // "expand_selected_onload" : true,
    //             "icons" : false,
    //             "dots" : false // no connecting dots between dots
    //         },
    //         'data' : [
    //             // {"id" : 1, "text" : "Node 1 <button>hello</button>"},
    //             {"id" : 1, "text" : "Node 1"},
    //             {"id" : 2, "text" : "Node 2"},
    //         ]
    //     },
    //     "plugins" : ["wholerow","dnd","contextmenu","changed"],
    //     "dnd" : {
    //         "large_drop_target" : true,
    //         "large_drag_target" : true
    //         // "blank_space_drop" : true
    //     }
    // }); });
    
    // $('#object-tree').on("changed.jstree", function (e, data) {
    //     console.log(data.selected);
    // });
});

function generateUUID() {
    return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
        (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
    );
}

// рекурсивный обход объекта
function traverseMap(map, parent_id, callback) {
    const result = [];

    // console.log(map);
    // console.log(typeof map);

    if (!map) return result;

    for (let [key, value] of map) {
        let current_id = 'jstree_node_' + generateUUID();
        let icon = jstree_icons.node_default;
        let name = escapeHtml(key.toString());

        let preview = { keys: '', head: '' };

        if (value !== null && value instanceof Map) {
            // Есть вложенные свойства (глубина 1 или больше)
            if (name == 'list') {
                // list не будет добавлен в дерево
                result.push(...traverseMap(value, parent_id, callback));
            } else {
                result.push(...traverseMap(value, current_id, callback));
                
                // Получаем ключи для preview (исключая 'list')
                preview.keys = Array.from(value.keys())
                    .filter(k => k !== 'list')
                    .join(', ');
                preview.keys = preview.keys ? `<i class="${jstree_icons.node_preview}"></i> ` + preview.keys : '';
                
                result.push(callback(name, value, current_id, parent_id, icon, preview));
            }
        }
    }

    // Object.keys(obj).forEach(function(key) {
        
    //     // console.log('parent ' + parent_id + ' have ' + key + ' with type ' + typeof key);

    //     let current_id = 'jstree_node_' + generateUUID();
    //     let icon = jstree_icons.node_default;
    //     let name = escapeHtml(key);
        
    //     // let preview = escapeHtml(obj[key]);
    //     let preview = {keys: '', head: ''};

    //     if (obj[key] !== null && typeof obj[key] === 'object') { //есть вложенные свойства (глубина 1 или больше)
    //         // console.log("traverse: " + name + " " + current_id + " ; parent:" + parent_id);
    //         if(name == 'list') { //list не будет добавлен в дерево
    //             result.push(...traverseMap(obj[key], parent_id, callback));
    //         } else {
    //             result.push(...traverseMap(obj[key], current_id, callback));
    //             preview.keys = Object.keys(obj[key]).filter(k => k !== 'list').join(', ');
    //             preview.keys = preview.keys ? `<i class="${jstree_icons.node_preview}"></i> ` + preview.keys : '';
    //             // preview = Object.keys(obj[key]).join(', ') === 'list' ? '' : '<i class="fa fa-angle-right"></i> ' + Object.keys(obj[key]).join(', ');
    //             // preview = Object.keys(obj[key]).join(', ').replace(/\blist\b/g, '<i class="fa fa-list"></i>');
    //             result.push(callback(name, obj[key], current_id, parent_id, icon, preview));
    //         }

    //         // result.push(...traverseMap(obj[key], current_id, callback));
    //         // icon = 'fa fa-list';
    //         // preview = '<i class="fa fa-angle-right"></i> '+ Object.keys(obj[key]).join(', ');
    //     }

    //     // result.push(callback(name, obj[key], current_id, parent_id, icon, preview));
    // });

    return result;
}

function createTreeItem(name, data, id, parent_id, icon = 'fa fa-times' , preview = {keys: '', head: ''}) { // TODO icons
    let tree_data = {};
    // console.log(name);
    // console.log(data);
    // console.log('-----------------');

    if (data instanceof Map) {
        for (let [key, value] of data) {
            if (typeof value !== 'object' && value !== null && value !== undefined) {
                tree_data[key] = value;
                icon = jstree_icons.node_default;
                if (key == 'head') preview.head = escapeHtml(value);
            }
        }
    }

    // if (typeof data === 'object' && data !== null) { // получить свойства элемента (без списков)
    //     Object.keys(data).forEach(function(key) {
    //         if (typeof data[key] !== 'object' && data[key] !== null && data[key] !== undefined) {
    //             tree_data[key] = data[key];
    //             icon = jstree_icons.node_default;
    //             if(key == 'head') preview.head = escapeHtml(data[key]); // добавить в предпросмотр название (при наличии)
    //         }
    //     });
    // }
    
    // tree_data['jstree-initial-name'] = name;
    let tree_item = icon === 'fa fa-times' ? {} : {
        id          : id,
        parent      : parent_id,
        text        : `<span class="tree-item-name">${name}</span> <span class="tree-item-head">${preview.head}</span> <span class="tree-item-preview">&nbsp;${preview.keys}</span>`,
        data        : tree_data,
        icon        : icon,
        li_attr     : {'jstree-initial-name': name}
    };

    // console.log("create: " + JSON.stringify(tree_item));
    // console.log("with data: " + JSON.stringify(data));

    return tree_item;
}

function formatNodeText(node = {}, new_name) {

    let name = new_name ?? node?.li_attr['jstree-initial-name'] ?? '';
    let preview = {keys: Object.keys(node?.data || {}).length ? (`<i class="${jstree_icons.node_preview}"></i> ` + Object.keys(node?.data || {}).join(', ')) : '',
                   head: node?.data?.head ? escapeHtml(node.data['head']) : ''};

    let text = `<span class="tree-item-name">${name}</span> <span class="tree-item-head">${preview.head}</span> <span class="tree-item-preview">&nbsp;${preview.keys}</span>`;

    return text;
}

function jsonToTreeConvert(json) {
    // const collection = JSON.parse(json);
    const collection = parseJsonToMap(json);
    // console.log(json);
    // console.log(collection);
    // const tree_data = traverseMap(collection, '#', createTreeItem);

    // root: data + preview
    // let root_keys = collection ? Object.keys(collection).filter(k => k !== 'list') : [];
    let root_keys = collection ? Array.from(collection.keys()).filter(k => k !== 'list') : [];
    let root_preview = {keys: root_keys.join(', '),
                        head: ''};
    root_preview.keys = root_preview.keys ? `<i class="${jstree_icons.node_preview}"></i> ` + root_preview.keys : '';

    let root_data = {};

    // Object.keys(collection || {}).forEach(function(key) {
    //     if (typeof collection[key] !== 'object' && collection[key] !== null && collection[key] !== undefined) {
    //         root_data[key] = collection[key];
    //         if(key == 'head') root_preview.head = escapeHtml(collection[key]);
    //     }
    // });
    if (collection) {
        for (let [key, value] of collection) {
            if (typeof value !== 'object' && value !== null && value !== undefined) {
                root_data[key] = value;
                if (key == 'head') root_preview.head = escapeHtml(value);
            }
        }
    }

    let tree_data = [{
        id          : 'jstree-root-node',
        parent      : '#',
        text        : `<span class="tree-item-name tree-root-name">[ root ]</span> <span class="tree-item-head">${root_preview.head}</span> <span class="tree-item-preview">&nbsp;${root_preview.keys}</span>`,
        icon        : `${jstree_icons.node_root} tree-root-name`,
        data        : root_data,
        li_attr     : {'jstree-initial-name': '[ root ]'}
    }];

    if(collection)
        tree_data.push(...traverseMap(collection, 'jstree-root-node', createTreeItem));
    
    tree_data = tree_data.filter(obj => Object.keys(obj).length !== 0);
    // console.log(tree_data);

    // The "text" you supply is actually treated as html by default:
    // data: [{id: 'x', text: '<button>hello</button>'}]

    // Expected format of the node (there are no required fields)
    // {
    // id          : "string" // will be autogenerated if omitted
    // text        : "string" // node text
    // icon        : "string" // string for custom
    // state       : {
    //     opened    : boolean  // is the node open
    //     disabled  : boolean  // is the node disabled
    //     selected  : boolean  // is the node selected
    // },
    // children    : []  // array of strings or objects
    // li_attr     : {}  // attributes for the generated LI node
    // a_attr      : {}  // attributes for the generated A node
    // }

    // Alternative format of the node (id & parent are required)
    // {
    // id          : "string" // required
    // parent      : "string" // required
    // text        : "string" // node text
    // icon        : "string" // string for custom
    // state       : {
    //     opened    : boolean  // is the node open
    //     disabled  : boolean  // is the node disabled
    //     selected  : boolean  // is the node selected
    // },
    // li_attr     : {}  // attributes for the generated LI node
    // a_attr      : {}  // attributes for the generated A node
    // }

    return tree_data;
}

function jstreeInit(json) {
    const tree_data = jsonToTreeConvert(json);
    // console.log(tree_data);
    // return;
    $("#object-tree").on('changed.jstree', function (e, data) { // нажатие на элемент
        //tree item clicked
        var i, j, r = [];
        for(i = 0, j = data.selected.length; i < j; i++) {
            r.push(data.instance.get_node(data.selected[i]).text);
        }
        // $('#event_result').html('Selected: ' + r.join(', '));
        // console.log('Selected: ' + r.join(', '));
        // console.log('clicked');
        // console.log(data);
        // console.log(data.node.children); //прямые потомки
        // console.log(data.node.children_d); //все потомки
        // $('#object-tree').jstree(true).get_node(data.node.id);

        let properties = data.node.data;
        if(properties) delete properties.id;
        // console.log(properties);
        propertiesTableLoadProperties(properties);
        $('.properties-table-controls.disabled').removeClass('disabled');
        $('.js-table-errors').removeClass('visible');
        $('.element-list').removeClass('disabled');
        $('.element-list').prop('inert', false);

    }).on('move_node.jstree', function (e, data) { // перемещение элемента
        //node moved
        jstreeCheckDuplicateNames('object-tree', data.node);

        // console.log('moved');
        // console.log(data);

        // const tree = $("#object-tree").jstree(true);

        // старый род элемент - простой если нет вложений, иначе список пока не найдет внутри себя простой элемент
        // обновить старый родительский элемент
        // if(data.old_parent != "jstree-root-node") {
        //     let node = tree.get_node(data.old_parent);
        //     let icon = Object.keys(node.children).length == 0 ? 'fa fa-minus' : 'fa fa-list';
        //     let preview = [];
        //     // console.log(node);
            
        //     // проверить дочерние элементы и подготовить соотв. иконку + предпросмотр
        //     Object.entries(node.children).forEach(([i, id]) => {
        //         let node_child = tree.get_node(id);
        //         preview.push(node_child.li_attr['jstree-initial-name']);

        //         // найти элемент без вложений = род. элем. является объектом
        //         if(Object.keys(node_child.children).length == 0) {
        //             icon = 'fa fa-archive';
        //         }
        //     });

        //     let preview_string = preview.length > 0 ? `<i class="fa fa-level-down"></i> ${preview.join(', ')}` : '';

        //     // обновить элемент
        //     tree.set_icon(node.id, icon);
        //     tree.rename_node(node.id, `<span class="tree-item-name">${node.li_attr['jstree-initial-name']}</span> <span class="tree-item-preview"> | ${preview_string}</span>`);
        // }
        
        // // новый род элемент - список, пока не найдет внутри себя простой элемент
        // // обновить новый родительский элемент
        // if(data.parent != "jstree-root-node") {
        //     let node = tree.get_node(data.parent);
        //     let icon = 'fa fa-list';
        //     let preview = [];
        //     // console.log(node);
            
        //     // проверить дочерние элементы и подготовить соотв. иконку + предпросмотр
        //     Object.entries(node.children).forEach(([i, id]) => {
        //         let node_child = tree.get_node(id);
        //         console.log();
        //         preview.push(node_child.li_attr['jstree-initial-name']);

        //         // найти элемент без вложений = род. элем. является объектом
        //         if(Object.keys(node_child.children).length == 0) {
        //             icon = 'fa fa-archive';
        //         }
        //     });

        //     // обновить элемент
        //     tree.set_icon(node.id, icon);
        //     tree.rename_node(node.id, `<span class="tree-item-name">${node.li_attr['jstree-initial-name']}</span> <span class="tree-item-preview"> | <i class="fa fa-level-down"></i> ${preview.join(', ')}</span>`);
        // }

        // console.log($("#object-tree").jstree('get_json',''));

    }).on("ready.jstree", function () { // завершение загрузки дерева
        // Open a specific node after the tree is ready
        $("#object-tree").jstree("open_node", "#jstree-root-node");
    }).on('set_text.jstree', function(e, data) { // начало переименования (после создания поля ввода)
        
        // ожидание появляения узла дерева
        const node_promise = new Promise(resolve => {

            const node = document.getElementById(data.obj.id);
            if (node) return resolve(node);
            
            const observer = new MutationObserver(() => {
                const node = document.getElementById(data.obj.id);
                if (node) {
                    observer.disconnect();
                    resolve(node);
                }
            });
            
            observer.observe(document.getElementById('object-tree'), {
                childList: true,
                subtree: true
            });
        });

        // узел создан
        node_promise.then(node_elem => {
            // ожидание появляения input внутри узла
            const input_promise = new Promise(resolve => {
                const input = node_elem.querySelector('.jstree-rename-input');
                if (input) return resolve(input);
                
                const observer = new MutationObserver(() => {
                    const input = node_elem.querySelector('.jstree-rename-input');
                    if (input) {
                        observer.disconnect();
                        resolve(input);
                    }
                });
                
                observer.observe(node_elem, {
                    childList: true,
                    subtree: true
                });
            });

            // input создан
            input_promise.then(input => {
                const match = data.obj.text.match(/<span class="tree-item-name">([^.= \r]+)<\/span>/);
                const text = match ? match[1] : '';
                
                $(input).val(text)
                        .attr('placeholder', 'Введите название')
                        .css('width', 'auto')
                        .on('input paste keydown', filterInput)
                        .one('blur', function() {
                            $(this).off('input paste keydown');
                        });
            });
        });

        // закомментированная версия кода в определенных случаях не ловила node_elem

        // const node_elem = document.getElementById(data.obj.id);
        
        // if (!node_elem) return;
        
        // const observer = new MutationObserver((mutations) => {
        //     const input = node_elem.querySelector('.jstree-rename-input');
        //     if (input) {
        //         observer.disconnect();
        //         const match = data.obj.text.match(/<span class="tree-item-name">([^.= \r]+)<\/span>/);
        //         const text = match ? match[1] : '';
        //         $(input).val(text);
        //         $(input).attr('placeholder', 'Введите название');
        //         // console.log('Input:', text);

        //         $(input).css('width', 'auto');
        //         $(input).on('input paste keydown', filterInput);

        //         // Удаляем обработчики после завершения редактирования
        //         $(input).one('blur', function() {
        //             $(this).off('input paste keydown');
        //         });
        //     }
        // });

        // observer.observe(node_elem, {
        //     childList: true,
        //     subtree: true
        // });

        // console.log($(`#${data.obj.id}`).find('.jstree-rename-input'));
        // console.log(data);
    }).on('rename_node.jstree', function(e, data) { // завершение переименования
        if (data.text === data.old) return; // Редактирование отменено (ESC или без изменений)
        
        var tree = $('#object-tree').jstree(true);
        var node = data.node;
        node.li_attr['jstree-initial-name'] = data.text;

        // let preview = node.data ? Object.keys(node.data).join(', ') : '';
        // preview = preview ? `<i class="${jstree_icons.node_preview}"></i> ` + preview : '';
        
        // node.text = `<span class="tree-item-name">${data.text || 'element'}</span>
        //              <span class="tree-item-preview">&nbsp;${preview}</span>`;
        node.text = formatNodeText(node);

        jstreeCheckDuplicateNames('object-tree', data.node);

        tree.redraw_node(node);
    })
    .jstree({
        "core" : {
            // "check_callback" : true,
            'check_callback': function (operation, node, parent, position) {
                if (parent.id === '#') {
                    return operation === 'create_node' && node.id === 'jstree-root-node';
                }
                if (operation === 'move_node' && node.id == 'jstree-root-node') {
                    return false;
                }
                return true;
            },
            "multiple" : false, // no multiselection
            "themes" : {
                "variant" : "large",
                // "stripes" : true,
                // "expand_selected_onload" : true,
                "icons" : true,
                "dots" : false // no connecting dots between nodes
            },
            'data' : tree_data
        },
        "plugins" : ["wholerow","dnd","contextmenu","changed"],
        "dnd" : {
            "large_drop_target" : true,
            "large_drag_target" : true
            // "blank_space_drop" : true
        },
        'contextmenu': {
            'items': function(node) {
                // для корневого элемента доступно только добавление вложений
                if(node.id == 'jstree-root-node') return {
                    'create': {
                        'label': "Добавить элемент",
                        'action': function(data) {
                            const inst = $.jstree.reference(data.reference);
                            const newNode = inst.create_node(node, {
                                id: 'jstree_node_' + generateUUID(),
                                text: formatNodeText({}, createUniqueName()), 
                                // text: `<span class="tree-item-name">new_element</span>
                                //        <span class="tree-item-preview">&nbsp;</span>`,
                                icon: `${jstree_icons.node_default}`
                            });
                            inst.edit(newNode);
                        }
                    }
                }
                // для других элементов доступны все операции
                return {
                    // Создать элемент
                    'create': {
                        'label': "Добавить элемент",
                        'action': function(data) {
                            const inst = $.jstree.reference(data.reference);
                            const newNode = inst.create_node(node, {
                                id: 'jstree_node_' + generateUUID(),
                                text: formatNodeText({}, createUniqueName()), 
                                // text: `<span class="tree-item-name">new_element</span>
                                //        <span class="tree-item-preview">&nbsp;</span>`,
                                icon: `${jstree_icons.node_default}`
                            });
                            inst.edit(newNode);
                        }
                    },
                    // Переименовать
                    'rename': {
                        'label': "Переименовать",
                        'action': function(data) {
                            const inst = $.jstree.reference(data.reference);
                            inst.edit(node);
                        }
                    },
                    // Удалить
                    'delete': {
                        'label': "Удалить",
                        'action': function(data) {
                            const inst = $.jstree.reference(data.reference);
                            inst.delete_node(node);
                            jstreeCheckDuplicateNames('object-tree', data.node);
                            $('.properties-table-controls').addClass('disabled');
                            const fragment = document.createDocumentFragment();
                            $(fragment).append(`
                                <tr>
                                    <td class="js-row-no-properties" colspan="3">Выберите элемент</td>
                                </tr>
                            `);
                            $('#table-properties').empty().append(fragment);
                        }
                    },
                    'copy': {
                        'label': "Дублировать",
                        'action': function(data) {
                            const inst = $.jstree.reference(data.reference);
                            const copy_name = createUniqueName('object-tree', node.li_attr['jstree-initial-name']);
                            // const copy_name = node.li_attr['jstree-initial-name'] + '_copy';
                            // let preview = node.data ? Object.keys(node.data).join(', ') : '';
                            // preview = preview ? `<i class="${jstree_icons.node_preview}"></i> ` + preview : '';
                            const node_text = formatNodeText((data?.node || {}), copy_name); 
                            // const node_text = `<span class="tree-item-name">${copy_name}</span>
                            //                    <span class="tree-item-preview">&nbsp;${preview}</span>`;
                            
                            const node_copy = {
                                id: 'jstree_node_' + generateUUID(),
                                icon: `${jstree_icons.node_default}`,
                                text: node_text,
                                li_attr: {
                                    ...node.li_attr,
                                    'jstree-initial-name': copy_name
                                },
                                data: JSON.parse(JSON.stringify(node.data || {}))
                            };
                            
                            var sibling_index = $('#object-tree').find('#' + node.id).index();

                            inst.create_node(node.parent, node_copy, sibling_index+1);

                            jstreeCheckDuplicateNames('object-tree', data.node);
                        }
                    },
                };
            }
        }
    });
}

function createUniqueName(tree_id = 'object-tree', base_name = jstree_config['node_base_name']) {    
    const tree = $(`#${tree_id}`).jstree(true);
    const all_nodes = tree.get_json(null, { flat: true });
    
    let max_number = 0;
    const name_pattern = base_name !== null ? new RegExp(`^${base_name}(?:_(\\d+))?$`) : null;
    // const name_pattern = new RegExp(`^${base_name}(?:_(\\d+))?$`);
   
    all_nodes.forEach(node => { // Ищем максимальный номер
        if (node.li_attr?.['jstree-initial-name']) {

            // Если base_name == null, ищем число в имени
            if (base_name === null) {
                let numMatch = node.li_attr['jstree-initial-name'].match(/^(\d+)$/);
                if (numMatch) {
                    const num = parseInt(numMatch);
                    if (num > max_number) max_number = num;
                }
            } else {
                const match = node.li_attr['jstree-initial-name'].match(name_pattern);
                if (match && match[1]) {
                    const num = parseInt(match[1]);
                    if (num > max_number) max_number = num;
                } else if (match) {
                    // Случай когда имя точно равно baseName (без номера)
                    max_number = Math.max(max_number, 1);
                }
            }

        }
    });
    
    let new_name;
    if (base_name === null) {
        new_name = (max_number + 1).toString();
    } else {
        new_name = max_number === 0 ? base_name : `${base_name}_${max_number + 1}`;
    }

    // const new_name = max_number === 0 ? base_name : `${base_name}_${max_number + 1}`;

    return new_name;
}

function treeAddNode() {
    const tree = $('#object-tree').jstree(true);
    // const base_name = "new_element";
    
    // const all_nodes = tree.get_json(null, { flat: true });
    
    // let max_number = 0;
    // const name_pattern = new RegExp(`^${base_name}(?:_(\\d+))?$`);
   
    // all_nodes.forEach(node => { // Ищем максимальный номер
    //     if (node.li_attr?.['jstree-initial-name']) {
    //         const match = node.li_attr['jstree-initial-name'].match(name_pattern);
    //         if (match && match[1]) {
    //             const num = parseInt(match[1]);
    //             if (num > max_number) max_number = num;
    //         } else if (match) {
    //             // Случай когда имя точно равно baseName (без номера)
    //             max_number = Math.max(max_number, 1);
    //         }
    //     }
    // });
    
    // const new_name = max_number === 0 ? base_name : `${base_name}_${max_number + 1}`;
    const new_name = createUniqueName();
    const node_text = formatNodeText({}, new_name);
    // const node_text = `<span class="tree-item-name">${new_name}</span>
    //                    <span class="tree-item-preview">&nbsp;</span>`;
    
    tree.create_node('jstree-root-node', {
        "text": node_text,
        "icon": jstree_icons.node_default,
        "li_attr": {
            "jstree-initial-name": new_name
        },
        "data": {}
    }, "last");
}

function propertiesTableAddItem(type = 'blank') {
    if($('#table-properties').find('.js-row-no-properties').length) 
        $('#table-properties').empty();

    const fragment = document.createDocumentFragment();
    $(fragment).append(`
        <tr class="property-row">
            <td><input onchange="" value="${property_types[type]['type_name']}" placeholder="Имя свойства" class="form-control js-property-name"></td>
            <td><input onchange="" value="" placeholder="${property_types[type]['value_placeholder']}" class="form-control js-property-value"></td>
            <td class="text-center" style="width:1em">
                <div class="infobtn btn btn-circle btn-outline btn-warning fa fa-remove js-properties-table-remove-item"></div>
            </td>
        </tr>
    `);

    $('#table-properties').append(fragment);
    jstreeUpdatePreview();
}

$('#table-properties').on('click', '.js-properties-table-remove-item', function() {
    $(this).closest('.property-row').remove();
    jstreeUpdatePreview();
    if(!$('#table-properties').has('.property-row').length) {
        const fragment = document.createDocumentFragment();
        $(fragment).append(`
            <tr>
                <td class="js-row-no-properties" colspan="3">У выделенного элемента нет свойств</td>
            </tr>
        `);
        $('#table-properties').append(fragment);
    }
});

$('#table-properties').on('input paste keydown', '.js-property-value', function(e) {
    const tree = $('#object-tree');
    const node = tree.jstree('get_selected', true)[0];

    //property name
    let property = $(this).closest('.property-row').find('.js-property-name').val();
    node['data'][property] = $(this).val();

    jstreeUpdatePreview();
});

$('#table-properties').on('input paste keydown', '.js-property-name', function(e) {
    //filter forbidden characters
    filterInput(e);

    //tree element preview
    jstreeUpdatePreview();

    //value placeholder
    let $element = $(this).closest('.property-row').find('.js-property-value');
    if (this.value in property_types) {
        $element.attr('placeholder', property_types[this.value]['value_placeholder']);
    } else {
        $element.attr('placeholder', property_types['blank']['value_placeholder']);
    }
});

function propertiesTableToObject() {
    const keys = {};
    
    $('.property-row').removeClass('duplicate-row').each(function() {
        const key = $(this).find('.js-property-name').val().trim();
        if (!key) return;
        
        if (keys[key]) {
            $(this).addClass('duplicate-row');
            keys[key].push(this);
        } else {
            keys[key] = [this];
        }
    });
    
    // Подсветка всех дубликатов
    let duplicated = false;
    $('.js-table-errors').removeClass('visible');
    $('.element-list').removeClass('disabled');
    $('.element-list').prop('inert', false);
    Object.entries(keys).forEach(([key, elements]) => {
        if (elements.length > 1) {
            elements.forEach(el => $(el).addClass('duplicate-row'));
            duplicated = true;
        }
    });

    if(duplicated) {
        $('.js-table-errors').addClass('visible');
        $('.element-list').addClass('disabled');
        $('.element-list').prop('inert', true);
    }

    const result = {};
    
    $('#table-properties .property-row').each(function() {
        const key = $(this).find('.js-property-name').val().trim();
        const value = $(this).find('.js-property-value').val().trim();
        if (key && key.length > 0) result[key] = value;
    });
    
    return result;
}

function jstreeUpdatePreview() {
    const tree = $('#object-tree');
    const node = tree.jstree('get_selected', true)[0];
    node.data = propertiesTableToObject();

    // console.log(node);

    // let preview = node.data ? Object.keys(node.data).join(', ') : '';
    // preview = preview ? `<i class="${jstree_icons.node_preview}"></i> ` + preview : '';

    node.text = formatNodeText(node, (node.li_attr['jstree-initial-name'] || 'element'));
    // node.text = `<span class="tree-item-name">${node.li_attr['jstree-initial-name'] || 'element'}</span>
    //              <span class="tree-item-preview">&nbsp;${preview}</span>`;
    
    tree.jstree('set_text', node.id, node.text);
    tree.jstree('redraw_node', node.id); // перерисовка элемента
}

function propertiesTableLoadProperties(properties) {
    const fragment = document.createDocumentFragment();

    if(Object.keys(properties || {}).length != 0) {
        Object.entries(properties).forEach(([key, value]) => {
            let placeholder = property_types?.[key]?.['value_placeholder'] ?? property_types['blank']['value_placeholder'];
            $(fragment).append(`
                <tr class="property-row">
                    <td><input onchange="" value="${key}" placeholder="Имя свойства" class="form-control js-property-name"></td>
                    <td><input onchange="" value="${value}" placeholder="${placeholder}" class="form-control js-property-value"></td>
                    <td class="text-center" style="width:1em">
                        <div class="infobtn btn btn-circle btn-outline btn-warning fa fa-remove js-properties-table-remove-item"></div>
                    </td>
                </tr>
            `);
        });
    } else {
        $(fragment).append(`
            <tr>
                <td class="js-row-no-properties" colspan="3">У выделенного элемента нет свойств</td>
            </tr>
        `);
    }
    
    $('#table-properties').empty().append(fragment);
}

function jstreeCheckDuplicateNames(tree_id = 'object-tree', changed_node) {
    const tree = $(`#${tree_id}`).jstree(true);
    // убрать предыдущие ошибки дублирования (до переименования)
    $('.js-tree-errors').removeClass('visible');
    // console.log(changed_node);

    // Собираем все узлы сгруппированные по родителям
    const nodes_by_parent = {};
    const all_nodes = tree.get_json(null, { flat: true });
    let has_duplicates = false;
    
    all_nodes.forEach(node => {
        const parent = node.parent;
        nodes_by_parent[parent] = nodes_by_parent[parent] || [];
        nodes_by_parent[parent].push(node);
    });

    // Проверяем дубликаты на каждом уровне
    Object.values(nodes_by_parent).forEach(sibling_nodes => {
        const names_on_level = {};

        // Собираем частоту имен на текущем уровне
        sibling_nodes.forEach(node => {
            const name = node.li_attr && node.li_attr['jstree-initial-name'];
            if (name) {
                names_on_level[name] = names_on_level[name] || { count: 0, nodes: [] };
                names_on_level[name].count++;
                names_on_level[name].nodes.push(node);
                if (names_on_level[name].count > 1) has_duplicates = true;
            }
        });

        // console.log(names_on_level);

        // Обновляем классы для узлов на текущем уровне
        sibling_nodes.forEach(node => {
            const name = node.li_attr && node.li_attr['jstree-initial-name'];
            const should_have_class = name && names_on_level[name].count > 1;
            const current_class = (node.li_attr && node.li_attr['class']) || '';
            const has_class = current_class.includes('jstree-duplicate-node');
            
            if (should_have_class !== has_class) {
                const tree_node = tree.get_node(node.id);
                tree_node.li_attr = tree_node.li_attr || {};
                
                if(!isNaN(parseFloat(name)) && isFinite(name)) { // Убираем дублирование если имя узла числовое
                    // const last_duplicate_node =  names_on_level[name].nodes[0];
                    if(changed_node.id != tree_node.id) {
                        let max_number = 0;
                        const name_pattern = new RegExp(`^(?:(\\d+))?$`);
                        sibling_nodes.forEach(elem => { // Ищем максимальный номер на текущем уровне
                            if (elem.li_attr?.['jstree-initial-name']) {
                                const match = elem.li_attr['jstree-initial-name'].match(name_pattern);
                                if (match && match[1]) {
                                    const num = parseInt(match[1]);
                                    if (num > max_number) max_number = num;
                                }
                            }
                        });
                        
                        const new_name = max_number === 0 ? '100' : `${max_number + 1}`;
                        const node_text = formatNodeText(tree_node, new_name);
                        // const node_text = `<span class="tree-item-name">${new_name}</span>
                        //                 <span class="tree-item-preview">&nbsp;</span>`;

                        tree_node.text = node_text;
                        tree_node.li_attr['jstree-initial-name'] = new_name;
                        has_duplicates = false;
                    }
                } else { // Подсвечиваем узлы с одинаковыми string именами
                    if (should_have_class) {
                        tree_node.li_attr['class'] = current_class 
                            ? `${current_class} jstree-duplicate-node` 
                            : 'jstree-duplicate-node';
                    } else {
                        tree_node.li_attr['class'] = current_class
                            .replace('jstree-duplicate-node', '')
                            .trim();
                        
                        if (!tree_node.li_attr['class']) {
                            delete tree_node.li_attr['class'];
                        }
                    }
                }

                tree.redraw_node(tree_node.id);
            }
        });
    });

    if(has_duplicates) $('.js-tree-errors').addClass('visible');

    // Это предыдущая версия кода
    // Она проверяет дубликаты на всех уровнях

    // // Получаем все узлы (копия узлов)
    // const all_nodes = tree.get_json(null, { flat: true });
    
    // // Собираем частоту имен
    // const nameFrequency = {};
    // let has_duplicates = false;
    // all_nodes.forEach(node => {
    //     const name = node.li_attr && node.li_attr['jstree-initial-name'];
    //     if (name) nameFrequency[name] = (nameFrequency[name] || 0) + 1;
    //     if (nameFrequency[name] > 1) has_duplicates = true;
    // });
    // // console.log(nameFrequency);
    
    // // Обновляем классы и собираем узлы для перерисовки                
    // all_nodes.forEach(node => { // массив с копиями узлов
    //     const name = node.li_attr && node.li_attr['jstree-initial-name'];
    //     const should_have_class = name && nameFrequency[name] > 1;
    //     const current_class = node.li_attr['class'] || '';
    //     const has_class = current_class.includes('jstree-duplicate-node');
        
    //     // Проверяем необходимость изменений
    //     if (should_have_class !== has_class) {
    //         const tree_node = tree.get_node(node.id); //ссылка на узел дерева
                
    //         if (should_have_class) {
    //             // Добавляем класс
    //             tree_node.li_attr['class'] = current_class ? `${current_class} jstree-duplicate-node` : 'jstree-duplicate-node';
    //         } else {
    //             // Удаляем класс
    //             tree_node.li_attr['class'] = current_class.replace('jstree-duplicate-node', '').trim();
    //         }

    //         // console.log(tree_node);
    //         tree.redraw_node(tree_node);
    //     }
    // });
    // if(has_duplicates) $('.js-tree-errors').addClass('visible');
}

function filterInput(e) {
    const input = e.target;
    
    switch (e.type) {
        case 'paste':
            e.preventDefault();
            const cleanData = (e.originalEvent || e).clipboardData
                .getData('text')
                .replace(/[.= \r]/g, '');
            document.execCommand('insertText', false, cleanData);
            break;

        case 'keydown':
            if (['.', '=', ' ', 'Enter'].includes(e.key)) {
                e.preventDefault();
                return false;
            }
            break;

        case 'input':
            const cursorPos = input.selectionStart;
            const cleanValue = input.value.replace(/[.= \r]/g, '');
            if (input.value !== cleanValue) {
                input.value = cleanValue;
                input.setSelectionRange(cursorPos, cursorPos);
            }
            break;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// парсинг дерева в формат конфига (с list)
function jstreeToConfig(node, state = { hasEmptyNodes: false }) {
    const result = new Map();
    const name = node.li_attr['jstree-initial-name'];
    let is_empty = node.id == 'jstree-root-node' ? false : true;
    
    // Обрабатываем свойства текущего узла если он не пустой
    if (Object.keys(node.data).length) {
        is_empty = false;
        Object.entries(node.data).forEach(([key, value]) => {
            result.set(key, value);
        });
        // Object.assign(result, node.data); // Копируем данные из node.data
    }

    // Обрабатываем дочерние узлы
    if (node.children && node.children.length > 0) {
        is_empty = false;
        result.set('list', new Map(
            node.children.map(child => [
                child.li_attr['jstree-initial-name'],
                jstreeToConfig(child, state)
            ])
        ));
        // result.list = {};
        // node.children.forEach(child => {
            // const child_name = child.li_attr['jstree-initial-name'];
            // result.list[child_name] = jstreeToConfig(child, `${parent_path}list.${child_name}.`);
        // });
    }

    if(is_empty) {
        state.hasEmptyNodes = true;

        $('.js-tree-warnings').addClass('visible');

        const tree =  $('#object-tree').jstree(true);
        const tree_node = tree.get_node(node.id);
        const current_class = (node.li_attr && node.li_attr['class']) || '';
        tree_node.li_attr = tree_node.li_attr || {};
        tree_node.li_attr['class'] = current_class 
                            ? `${current_class} jstree-empty-node` 
                            : 'jstree-empty-node';
        
        tree.redraw_node(tree_node.id);
        openNodePath(tree, tree_node.id);
        
        // Убираем предупреждение через 5 секунд
        setTimeout(() => {
            $('.js-tree-warnings').removeClass('visible');
            
            const currentTreeNode = tree.get_node(tree_node.id);
            if (currentTreeNode && currentTreeNode.li_attr && currentTreeNode.li_attr['class']) {
                const updatedClass = currentTreeNode.li_attr['class']
                    .replace('jstree-empty-node', '')
                    .replace(/\s+/g, ' ')
                    .trim();
                
                if (updatedClass) {
                    currentTreeNode.li_attr['class'] = updatedClass;
                } else {
                    delete currentTreeNode.li_attr['class'];
                }
                
                tree.redraw_node(tree_node.id);
            }
        }, 5000);
    }
    
    return result;
}

function openNodePath(tree, node_id) {
    let current_id = node_id;
    const path = [];

    while (current_id && current_id !== '#') {
        path.unshift(current_id);
        current_id = tree.get_parent(current_id);
    }
    
    path.forEach(node_id => {
        if (!tree.is_open(node_id)) {
            tree.open_node(node_id);
        }
    });
}

async function saveObject(actionurl)
{
    const tree = $('#object-tree').jstree(true);
    const tree_data = tree.get_json('#', { flat: false });
    const state = { hasEmptyNodes: false };
    const config_format = tree_data.map(node => jstreeToConfig(node, state));
    // console.log(tree_data);

    if (state.hasEmptyNodes) {
        toastr.error('Сохранение недоступно', 'Ошибка в структуре объекта');
        return false;
    }

    let resultMap;
    if (config_format.length === 1) {
        resultMap = config_format[0];
    } else {
        resultMap = new Map([['list', new Map(
            config_format.map((item, index) => [index, item])
        )]]);
    }
    result = stringifyMap(resultMap, 0);

    // let result = config_format.length === 1 ? config_format[0] : { list: config_format };
    // result = JSON.stringify(result, mapReplacer);
    // result = JSON.stringify(result);
    // console.log(JSON.stringify(result, null, 2));
    // console.log(result);
    // return;
    
    return new Promise((resolve, reject) => {
        $.ajax({
            url: encodeURI(actionurl),
            type: "POST",
            data: { object: result },
            timeout: 15000, // 15 секунд
            success: function(response) {
                toastr.success('', 'Сохранено');
                resolve(response);
            },
            error: function(xhr, status, error) {
                let message = '';
                
                if (status === 'timeout') {
                    message = 'Превышено время ожидания сервера';
                }
                else if (!navigator.onLine) {
                    message = 'Нет интернет-соединения';
                }
                else if (!xhr.responseText) {
                    message = getHttpStatusMessage(xhr.status);
                }
                else {
                    message = xhr.responseText;
                }
                
                toastr.error(message, 'Ошибка сохранения');
                reject(new Error(message));
            }
        });
    });
}

function getHttpStatusMessage(code) {
    const messages = {
        400: 'Некорректный запрос',
        401: 'Требуется авторизация',
        403: 'Доступ запрещен',
        404: 'Страница не найдена',
        500: 'Внутренняя ошибка сервера',
        502: 'Ошибка шлюза',
        503: 'Сервис недоступен'
    };
    
    return messages[code] || `Ошибка сервера (код ${code})`;
}

// сериализовать map в json
function stringifyMap(map, indent = 0, visited = new WeakSet()) {
    if (visited.has(map)) {
        return '"<circular>"'; // Защита от циклических ссылок
    }
    visited.add(map);

    const indentStr = ' '.repeat(indent);
    const innerIndent = ' '.repeat(indent + 2);
    
    if (map.size === 0) return '{}';
    
    let json = '{\n';
    let first = true;
    
    for (let [key, value] of map) {
        if (!first) json += ',\n';
        first = false;
        
        const serializedKey = JSON.stringify(key);
        
        let serializedValue;
        if (value instanceof Map) {
            serializedValue = stringifyMap(value, indent + 2);
        } else if (typeof value === 'object' && value !== null) {
            serializedValue = JSON.stringify(value, null, 2).replace(/\n/g, '\n' + innerIndent);
        } else {
            serializedValue = JSON.stringify(value);
        }
        
        json += `${innerIndent}${serializedKey}: ${serializedValue}`;
    }
    
    json += `\n${indentStr}}`;
    return json;
}

// парсер json в map
function parseJsonToMap(jsonStr) {
    let index = 0;
    
    function parseValue() {
        skipWhitespace();
        
        if (jsonStr[index] === '{') {
            return parseObject();
        } else if (jsonStr[index] === '[') {
            return parseArray();
        } else if (jsonStr[index] === '"') {
            return parseString();
        } else if (jsonStr[index] === 't' && jsonStr.substr(index, 4) === 'true') {
            index += 4;
            return true;
        } else if (jsonStr[index] === 'f' && jsonStr.substr(index, 5) === 'false') {
            index += 5;
            return false;
        } else if (jsonStr[index] === 'n' && jsonStr.substr(index, 4) === 'null') {
            index += 4;
            return null;
        } else {
            return parseNumber();
        }
    }
    
    function parseObject() {
        index++; // Пропускаем '{'
        skipWhitespace();
        
        const map = new Map();
        
        if (jsonStr[index] === '}') {
            index++; // Пропускаем '}'
            return map;
        }
        
        while (index < jsonStr.length) {
            skipWhitespace();
            
            // Парсим ключ
            const key = parseValue();
            skipWhitespace();
            
            // Пропускаем двоеточие
            if (jsonStr[index] !== ':') {
                throw new Error('Expected colon');
            }
            index++;
            skipWhitespace();
            
            // Парсим значение
            const value = parseValue();
            map.set(key, value);
            
            skipWhitespace();
            
            if (jsonStr[index] === '}') {
                index++; // Пропускаем '}'
                break;
            }
            
            if (jsonStr[index] !== ',') {
                throw new Error('Expected comma or closing brace');
            }
            index++;
        }
        
        return map;
    }
    
    function parseArray() {
        index++; // Пропускаем '['
        skipWhitespace();
        
        const map = new Map();
        let arrayIndex = 0;
        
        if (jsonStr[index] === ']') {
            index++; // Пропускаем ']'
            return map;
        }
        
        while (index < jsonStr.length) {
            skipWhitespace();
            
            // Парсим значение элемента массива
            const value = parseValue();
            map.set(arrayIndex++, value);
            
            skipWhitespace();
            
            if (jsonStr[index] === ']') {
                index++; // Пропускаем ']'
                break;
            }
            
            if (jsonStr[index] !== ',') {
                throw new Error('Expected comma or closing bracket');
            }
            index++;
        }
        
        return map;
    }
    
    function parseString() {
        index++; // Пропускаем открывающую кавычку
        let result = '';
        
        while (index < jsonStr.length && jsonStr[index] !== '"') {
            if (jsonStr[index] === '\\') {
                index++;
                if (index >= jsonStr.length) throw new Error('Unexpected end of string');
                
                switch (jsonStr[index]) {
                    case '"': result += '"'; break;
                    case '\\': result += '\\'; break;
                    case '/': result += '/'; break;
                    case 'b': result += '\b'; break;
                    case 'f': result += '\f'; break;
                    case 'n': result += '\n'; break;
                    case 'r': result += '\r'; break;
                    case 't': result += '\t'; break;
                    case 'u':
                        if (index + 4 >= jsonStr.length) throw new Error('Invalid unicode escape');
                        const hex = jsonStr.substr(index + 1, 4);
                        result += String.fromCharCode(parseInt(hex, 16));
                        index += 4;
                        break;
                    default:
                        throw new Error('Invalid escape sequence');
                }
            } else {
                result += jsonStr[index];
            }
            index++;
        }
        
        if (jsonStr[index] !== '"') throw new Error('Unterminated string');
        index++; // Пропускаем закрывающую кавычку
        
        return result;
    }
    
    function parseNumber() {
        let start = index;
        
        // Парсим число
        if (jsonStr[index] === '-') index++;
        if (jsonStr[index] === '0') {
            index++;
        } else if (jsonStr[index] >= '1' && jsonStr[index] <= '9') {
            while (jsonStr[index] >= '0' && jsonStr[index] <= '9') index++;
        } else {
            throw new Error('Invalid number');
        }
        
        // Дробная часть
        if (jsonStr[index] === '.') {
            index++;
            while (jsonStr[index] >= '0' && jsonStr[index] <= '9') index++;
        }
        
        // Экспоненциальная часть
        if (jsonStr[index] === 'e' || jsonStr[index] === 'E') {
            index++;
            if (jsonStr[index] === '+' || jsonStr[index] === '-') index++;
            while (jsonStr[index] >= '0' && jsonStr[index] <= '9') index++;
        }
        
        const numStr = jsonStr.substring(start, index);
        return parseFloat(numStr);
    }
    
    function skipWhitespace() {
        while (index < jsonStr.length && 
               (jsonStr[index] === ' ' || jsonStr[index] === '\t' || 
                jsonStr[index] === '\n' || jsonStr[index] === '\r')) {
            index++;
        }
    }
    
    return parseValue();
}

// Функция для сериализации Map в JSON // это все равно промежуточно делает объект и сортирует числовые ключи :|
// function mapReplacer(key, value) {
//     if (value instanceof Map) {
//         return Array.from(value.entries()).reduce((obj, [k, v]) => {
//             obj[k] = v;
//             return obj;
//         }, {});
//     }
//     return value;
// }
// function mapReviver(key, value) {
//     if (value && value.__type === 'Map') {
//         return new Map(value.value);
//     }
//     return value;
// }

// function emptyObjectCheck(obj) {
//     if (typeof obj !== 'object' || obj === null) {
//         return false;
//     }
    
//     for (let key in obj) {
//         if (obj.hasOwnProperty(key)) {
//             if (typeof obj[key] === 'object' && obj[key] !== null) {
//                 if (!emptyObjectCheck(obj[key])) {
//                     return false;
//                 }
//             } else {
//                 return false;
//             }
//         }
//     }
//     return true;
// }