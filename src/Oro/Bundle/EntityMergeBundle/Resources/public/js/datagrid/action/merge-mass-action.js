/*global define*/
define(['underscore', 'orotranslation/js/translator', 'orodatagrid/js/datagrid/action/mass-action', 'oroui/js/messenger'],
    /**
     * @param {underscore} _
     * @param {orotranslation/js/translator} __
     * @param {MassAction} MassAction
     * @param {notificationFlashMessage} messenger
     * @returns {*|Object|void}
     */
    function (_, __, MassAction, messenger) {
        'use strict';

        /**
         * Merge mass action class.
         *
         * @export  oroentitymerge/js/datagrid/action/merge-mass-action
         * @class   oroentitymerge.datagrid.action.MergeMassAction
         * @extends orodatagrid.datagrid.action.MassAction
         */
        return MassAction.extend({

            /**
             * Initialize view
             *
             * @param {Object} options
             * @param {Object} [options.launcherOptions] Options for new instance of launcher object
             * @constructor
             */
            initialize: function (options) {
                /**
                 * @property {Number} max_element_count Max amount of merging elements
                 * @type {Number}
                 */
                var maxLength = this.max_element_count;
                MassAction.prototype.initialize.apply(this, arguments);
                /**
                 * @param {object} event Backbone event object
                 * @param {object} options Additional param options needed to stop action
                 */
                this.on('preExecute', function (event, options) {
                    /**
                     * @typedef {object} SelectedModelsHash Hash map
                     * @typedef {object} SelectionState
                     * @property {SelectedModelsHash} selectedModels
                     * @property {boolean} inset
                     */
                    var selectionState = this.datagrid.getSelectionState();
                    var isInset = selectionState.inset;
                    var length = Object.keys(selectionState.selectedModels).length;

                    if (!isInset) {
                        var totalRecords = this.datagrid.collection.state.totalRecords;

                        length = totalRecords - length;
                    }

                    if (length > maxLength) {
                        options.doExecute = false;
                        var validationMessage = __('oro.entity_merge.mass_action.validation.maximum_records_error', {number: maxLength});
                        messenger.notificationFlashMessage('error', validationMessage);
                    }

                    if (length < 2) {
                        options.doExecute = false;
                        messenger.notificationFlashMessage('error', __('oro.entity_merge.mass_action.validation.minimum_records_error'));
                    }

                }, this);
            }
        });
    });
