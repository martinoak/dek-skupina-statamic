filters = {};

Vue.component('filter-select', {
    data() {
        return {
            filled: false,
            show: false,
            selected: '',
            placeHolder: ''
        };
    },
    created() {
        /* This is adding an event listener to the window object. */
        window.addEventListener('resize', this.handleResize);
        /* This is a JavaScript event listener that listens for a click on the window. */
        window.addEventListener('click', this.windowClick);
        /* This code is adding a function to the window object. */
        this.handleResize();
    },
    mounted() {
        /* The code above is the code that is executed when the page loads. 
        It checks if the URL has a query string, and if it does, it parses the query string. 
        If the query string has a parameter named "detail", it checks if the parameter has a value of 1. 
        If it does, it checks if the URL has a parameter named "obor", "lokalita" and "uvazek". 
        If it does, it sets the corresponding localStorage item to the value of the parameter. 
        If it */


        /* Getting the query string from the URL and storing it in a variable. */
        const queryString = window.location.search;
        /* The above code is creating a new URLSearchParams object and passing the queryString variable
        to the constructor. */
        const urlParams = new URLSearchParams(queryString);

        if (urlParams.get('itm_source') !== null) {
            localStorage.setItem("itm_source", urlParams.get('itm_source'));
        } else {
            localStorage.removeItem('itm_source');
        }

        if (urlParams.get('itm_campaign') !== null) {
            localStorage.setItem("itm_campaign", urlParams.get('itm_campaign'));
        } else {
            localStorage.removeItem('itm_campaign');
        }


        const detail = urlParams.get('detail');
        let obor = null;
        let lokalita = null;
        let uvazek = null;

        if (detail == 1) {
            obor = localStorage.getItem("obor");
            lokalita = localStorage.getItem("lokalita");
            uvazek = localStorage.getItem("uvazek");
        }

        const pObor = urlParams.get('obor');
        const pLokalita = urlParams.get('lokalita');
        const pUvazek = urlParams.get('uvazek');

        const lang = document.getElementById("js-actual-lang").innerText;
        if (localStorage.getItem('lang') != lang) {
            localStorage.setItem('lang', lang);
            localStorage.removeItem('obor');
            localStorage.removeItem('lokalita');
            localStorage.removeItem('uvazek');
        }

        if (pObor != null) obor = pObor;
        if (pLokalita != null) lokalita = pLokalita;
        if (pUvazek != null) uvazek = pUvazek;

        if (obor != null && this.name == 'obor') {
            this.filled = true;
            this.selected = obor;
        }
        if (lokalita != null && this.name == 'lokalita') {
            this.filled = true;
            this.selected = lokalita;
        }
        if (uvazek != null && this.name == 'uvazek') {
            this.filled = true;
            this.selected = uvazek;
        }

        /* If the user has not selected a subject, then remove the subject from local storage. */
        if (obor == null && lokalita == null && uvazek == null) {
            localStorage.removeItem('obor');
            localStorage.removeItem('lokalita');
            localStorage.removeItem('uvazek');
        }

    },
    methods: {
        windowClick(e) {
            if (!this.$el.contains(e.target)) {
                this.show = false;
            }
        },
        handleResize() {
            /* This code is checking if the window width is greater than 711 pixels. If it is, then the placeHolder
            variable is set to the default value. If it is not, then the placeHolder variable is set to the
            shorter value. */
            this.placeHolder = (window.innerWidth > 711) ? this.placeHolderDefault : this.placeHolderShort;
        },
        clear() {
            /* Remove the item from local storage. */
            localStorage.removeItem(this.name);
            this.filled = false;
            this.selected = false;
        },
        fill(selected) {
            /* If the user has selected a value, then the dropdown is filled and the selected value is displayed.
            If the user has not selected a value, then the dropdown is not filled and the null value is
            displayed. */
            this.filled = (selected !== this.nullValue);
            this.selected = selected;
            this.dropdown();
        },
        dropdown() {
            this.show = !this.show;
        },
        changeUrlParams() {
            /* 1. Get the values of the form fields from local storage.
            2. Build a URL string with the values of the form fields.
            3. Replace the URL of the current page with the URL string.
            
            # **Step 4:** Add the event listener to the form.
            
            # **Step 5:** Add the event listener to the form.
            
            # **Step 6:** Add the event listener to the form.
            
            # **Step 7:** Add the event listener to the form.
            
            # **Step 8:** Add the */
            const obor = localStorage.getItem("obor");
            const lokalita = localStorage.getItem("lokalita");
            const uvazek = localStorage.getItem("uvazek");

            const itm_source = localStorage.getItem("itm_source");
            const itm_campaign = localStorage.getItem("itm_campaign");

            let url = "";
            if (obor != null) {
                url = `${url}&obor=${obor}`;
            }
            if (lokalita != null) {
                url = `${url}&lokalita=${lokalita}`;
            }
            if (uvazek != null) {
                url = `${url}&uvazek=${uvazek}`;
            }

            if (itm_source !== null) {
                url = `${url}&itm_source=${itm_source}`;
            }
            if (itm_campaign !== null) {
                url = `${url}&itm_campaign=${itm_campaign}`;
            }

            if (url != "") {
                url = `?${url.slice(1)}`;
            }

            /* Replacing the current state of the history with a new state. */
            window.history.replaceState(null, null, `volna-pracovni-mista${url}`);

        },
        filter() {
            /* For each row in the table, if the row's data matches the filter, display it. Otherwise, hide it. */
            const rows = document.getElementById("table").getElementsByClassName("employment-row");
            const h2kar = document.getElementsByClassName("h2kariera");
            var i;
            for (i = 0; i < h2kar.length; i++) {
                h2kar[i].style.display = 'none';
            }

            const steps = rows.length;
            let skryte = 0;
            for (var i = 0; i < steps; i++) {
                const content = rows[i].dataset;
                /* The above code is using the compare function to determine if the content of the row should be
                displayed or not. */
                rows[i].parentElement.style.display = (this.compare(content)) ? 'block' : 'none';

                if (!this.compare(content)) {
                    skryte++;
                }

                document.getElementById("employment-zero").style.display = skryte == steps - 1 ? 'block' : 'none';

                if (this.compare(content)) {
                    var aktual = rows[i].closest("a").previousElementSibling;
                    if (aktual.tagName == 'H2') {
                        aktual.style.display = 'block';
                    } else {
                        for (let j = 0; j < i; j++) {
                            aktual = aktual.previousElementSibling;
                            if (aktual.tagName == 'H2') {
                                aktual.style.display = 'block';
                                break;
                            }
                        }
                    }
                }
            }
            document.getElementById("table").style.display = 'block';
        },
        compare(content) {
            /* If the content of the article matches the filters, return true. Otherwise, return false. */
            for (const key in filters) {
                if (filters[key]) {
                    if (content[key].includes(filters[key])) {
                        continue;
                    } else {
                        return false;
                    }
                }
            }

            return true;
        }
    },
    watch: {
        selected(val) {
            /* If the value is not false, set the localStorage item to the value. If the value is false, remove the
            localStorage item. */
            if (val != false) {
                localStorage.setItem(this.name, val);
            } else {
                localStorage.removeItem(this.name);
            }
            this.changeUrlParams();
            filters[this.name] = (val !== this.nullValue) ? val : false;
            this.filter();
        }
    },
    props: ['name', 'items', 'nullValue', 'placeHolderShort', 'placeHolderDefault'],
    filters: {
        /**
         * Convert a string to a URL-friendly format.
         * 
         * The function takes a string as an argument and returns a URL-friendly version of the string.
         * 
         * The function first converts the string to lowercase.
         * 
         * Then it iterates over the string, replacing characters with their URL-friendly equivalents.
         * 
         * The URL-friendly characters are defined in a hash table called nodiac.
         * 
         * The function then replaces all non-alphanumeric characters with dashes.
         * 
         * The function then removes the leading and trailing dashes.
         * 
         * The function returns the URL-friendly string.
         * @param s - the string to be converted
         * @returns The function returns the string with all diacritics removed.
         */
        webalize(s) {
            const nodiac = {
                'á': 'a',
                'č': 'c',
                'ď': 'd',
                'é': 'e',
                'ě': 'e',
                'í': 'i',
                'ň': 'n',
                'ó': 'o',
                'ř': 'r',
                'š': 's',
                'ť': 't',
                'ú': 'u',
                'ů': 'u',
                'ý': 'y',
                'ž': 'z'
            };
            s = s.toLowerCase();
            let s2 = '';
            for (let i = 0; i < s.length; i++) {
                s2 += (typeof nodiac[s.charAt(i)] !== 'undefined' ? nodiac[s.charAt(i)] : s.charAt(i));
            }
            return s2.replace(/[^a-z0-9_]+/g, '-').replace(/^-|-$/g, '');
        }
    },
    template: "<div class='holder' :data-name='name' >\n\
            <div class='selected-value' v-on:click='dropdown()'>\n\
                <span class='carr-sellector' v-if='!filled || selected == nullValue'>{{placeHolder}}\n\
                <span class='null-value'></span></span><span v-else>{{selected}} \n\
                <span v-on:click='clear()' class='clear'></span></span>\n\
            </div>\n\
            <div v-if='show' class='list'>\n\
                <div class='item' :data-value='item' v-for='item in items' v-on:click='fill(item)'>\n\
                   <span v-if='selected == item || (item == nullValue && !filled)'> </span>\n\
                   <span class='nameFilerSelect' :class='item|webalize'>{{ item }}</span>\n\
                </div>\n\
            </div>\n\
        </div>"
});