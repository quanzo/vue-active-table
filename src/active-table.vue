<template>    
    <div>
      <div class="error" v-if="errorMsg">{{errorMsg}}</div>
      <v-table :data="data" :columns="columns" :useUndo="tblUseUndo" :defEnableEdit="tblDefEnableEdit" :defEnableSort="tblDefEnableSort" :enableSort="tblEnableSort" :enableEdit="tblEnableEdit" :enableChecked="tblEnableChecked" :sort="tblSort" :checkedRows="tblCheckedRows" :blockedRows="tblBlockedRows" :rowClasses="tblRowClasses" @event-end-edit-cell="onEditCell" @event-sort-table="onSort" @event-new-row="onNewRow"></v-table>
      <slot></slot>
    </div>
</template>

<script>
const Table = require("@quanzo/vue-table");
const Request = require("@codexteam/ajax");
export default {
  name: "ActiveTable",
  inject: [],

  props: {
    
    url: {
      type: String,
      default: ""
    },
    
    params: {
      type: Object,
      default: function () {
        return {};
      }
    },

    headers: {
      type: Object,
      default: function () {
        return {};
      }
    },

    useUndo: {
      type: Boolean,
      default: true
    },
    
    defEnableEdit: {
      type: Boolean,
      default: true
    },
    
    defEnableSort: {
      type: Boolean,
      default: true
    },
    
    enableChecked: {
      type: Boolean,
      default: true
    },
    /** Сортировка
     * массив объектов, порядок в массиве - приоритет сортировки
     * { col: <имя колонки>, direct: "asc" или "desc"}
     */
    sort: {
      type: Array,
      default: function () {
        return [];
      }
    },

    /** Фильтр данных. Объект.
     * Ключ свойства - имя колонки
     * Значение свойства - значение фильтра
     */
    filter: {
      type: Object,
      default: function () {
        return {};
      }
    },

    page: {
      type: Number,
      default: 1
    },

    perpage: {
      type: [Number, Boolean],
      default: false
    },

    msgTimeout: {
      type: Number,
      default: 15000
    }

  },
  components: {
    "v-table": Table.Table(),    
  },
  data() {
    return {
      events: {},
      data: [],
      columns: false,
      key: false,
      requests: {},
      errorMsg: "",

      tblUseUndo: this.useUndo,
      tblDefEnableEdit: this.defEnableEdit,
      tblDefEnableSort: this.defEnableSort,
      tblEnableChecked: this.enableChecked,
      tblSort: this.sort,
      tblPerpage: this.perpage,
      tblPage: this.page,

      tblCheckedRows: [],
      tblBlockedRows: [],
      tblRowClasses: [],
      tblPages: 0,

      tblEnableEdit: true,
      tblEnableSort: true,

      backupState: {}
    };
  },
  
  methods: {
    onEditCell(e) {
      console.log("onEditCell", e);
      this.sendRequest("change", {
        event: e
      });
    },

    onSort(e) {
      console.log("onSort", e);
      this.sendRequest("sort", {
        event: e
      });
    },

    onNewRow(e) {
      console.log("onNewRow", e);
      this.sendRequest("newRow", {
        event: e
      });
    },

    sendRequest(method, reqParams = {}) {
      var tbl = this;
      
      // запрет на изменение таблицы пока идет запрос
      this.enableEdit = false;
      this.enableSort = false;
      
      // prepare params for request
      var sendParams = {
        method: method,
        params: {
          blockedRows: this.tblBlockedRows,
          checkedRows: this.tblCheckedRows,
          rowClasses: this.tblRowClasses,
          sort: this.tblSort,
          filter: this.filter
        },
        id: Date.now()
      };
      if (this.tblPerpage > 0) {
        sendParams.params.perpage = this.tblPerpage;
        if (!this.tblPage || this.tblPage <= 0) {
          this.tblPage = 1;
        }
        sendParams.params.page = this.tblPage;
      }

      sendParams.params = Object.assign(tbl.params, sendParams.params, reqParams);
      // register request
      this.requests[sendParams.id] = sendParams;


      console.log("sendReq", sendParams);
      // send request
      Request.post({
        url: tbl.url,
        headers: tbl.headers,
        type: Request.contentType.URLENCODED,
        data: sendParams,
        progress: tbl.progressBar
      }).then(tbl.processResponse);
    },

    processResponse(response) {
      console.log("Response", response);


      if (response.code == 200) {
        
        if (typeof response.body.id != "undefined") {
          let respID = response.body.id;
          console.log("respID", respID);

          


          if (this.requests[respID] != "undefined") {
            var respRequest = this.requests[respID];
            delete this.requests[respID];
            console.log("respRequest", respRequest);
            if (typeof response.body.result != "undefined") { // success
              var result = response.body.result;
              /***/              
              
              if (typeof result.data == "object") {
                this.data = result.data;
              }
              /***/
              if (typeof result.columns == "object") {
                this.columns = result.columns;
              }
              /****/
              let arReqParams = ["blockedRows", "checkedRows", "rowClasses", "sort"];
              let arCmpParams = ["tblBlockedRows", "tblCheckedRows", "tblRowClasses", "tblSort"];
              for (let i=0; i < arCmpParams.length; i++) {
                if (Array.isArray(result[arReqParams[i]])) {
                  this[arCmpParams[i]] = result[arReqParams[i]];
                }
              }             
              /****/
              if (typeof result.pages != "undefined") {
                this.tblPages = result.pages;
              }
              /****/
              if (typeof result.key != "undefined") {
                this.key = result.key;
              }
              /***/
              switch (respRequest.method) {
                case "newRow": 
                  if (typeof result.row_data != "undefined") {                    
                    this.data[respRequest.params.event.afterRow + 1] = result.row_data;                    
                    //respRequest.params.event.data = result.row_data;
                  }
                  console.log("newRow", result, respRequest.params.event.data, this.data);
                break;
              }
            } else if (typeof response.body.error != "undefined") { // request rollback
              console.log("Error req!", respRequest);
              
              this.errorMsg = response.body.error.message;

              switch (respRequest.method) {
                case "change":
                  respRequest.params.event.data[respRequest.params.event.col] = respRequest.params.event.initial;                  
                  break;
                case "newRow": // отмена вставки строки
                  //respRequest.params.event.data
                  console.log(respRequest);
                  var newRowIndex = respRequest.params.event.afterRow + 1;
                  if (this.data[newRowIndex] == respRequest.params.event.data) {
                    this.data.splice(newRowIndex, 1);
                  }                  
                  break;
              } // end switch
            }
          }
        }             
      }

      if (Object.keys(this.requests).length == 0) {
        // снять запрет на изменение таблицы
        this.enableEdit = true;
        this.enableSort = true;
      }
    },

    progressBar(percent) {
      console.log("%", percent);
    },    
  
    registerEventListener(eventName, f) {
      if (typeof this.events[eventName] != "object") {
        this.events[eventName] = [];
      }
      this.events[eventName][this._events[eventName].length] = f;
    },

    startEvent(eventName, params = {}) {
      if (typeof this.events[eventName] == "object") {
        let funcs = this.events[eventName];
        for (var idx in funcs) {
          if (typeof funcs[idx] == "function") {
            funcs[idx](params);
          }
        }
      }
    }

  },

  computed: {
    
  },

  watch: {
    errorMsg(val, old) {
      if (val) {
        var tbl = this;
        setTimeout(() => {
          tbl.errorMsg = "";
        }, this.msgTimeout);
      }
    }
  },

  mounted() {
    // запрос на начальное получение данных
    this.sendRequest("load", {});
  },
  
  provide() {
    
  }

};
</script>
 
<style>
</style>