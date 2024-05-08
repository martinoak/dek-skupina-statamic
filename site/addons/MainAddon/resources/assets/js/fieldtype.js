/*
    Statamic nemel (nebo alespon jsem nikde nenasel) integer input s moznosti desetinnych cisel a to bylo treba na HP pro sekci DEK v cislech
    nebylo ani treba to delat pres sablony, cely to zajistuje VUE.JS
*/
Vue.component('main_addon-fieldtype', {

    mixins: [Fieldtype],

    template: '<div><input type="number" class="form-control type-number" v-model="data" tabindex="0" :autofocus="autofocus" :step="step" :min="min" :max="max" /></div>',

    data: function () {
        return {
            //
        };
    },

    computed: {
        step() {
            return this.config.step;
        },
        min() {
            return this.config.min;
        },
        max() {
            return this.config.max;
        }
    },

    methods: {
        //
    },

    ready: function () {
        //
    }

});


/*
 * 
 * Vue.component('float-fieldtype', {
 
 mixins: [Fieldtype],
 
 template: '<input type="number" class="form-control type-number" v-model="data" tabindex="0" :autofocus="autofocus" :step="step" :min="min" :max="max" />',
 
 computed: {
 ,
 },
 
 });
 */