import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['roleMapping'];

  connect() {
    this.roleMappingIdx = this.roleMappingTarget.dataset.index;
    [...this.roleMappingTarget.firstElementChild.children].forEach(this._formatAndAddDelete.bind(this));
  }

  addRoleMapping() {
    const prototype = this.roleMappingTarget.dataset.prototype;
    const row = document.createElement('div');
    row.innerHTML = prototype.replace(/__name__/g, this.roleMappingIdx);

    this.roleMappingTarget.firstElementChild.append(row);
    this._formatAndAddDelete(row);

    this.roleMappingIdx++;
  }

  _formatAndAddDelete(formRow) {
    const innerRow = formRow.firstElementChild;
    [...innerRow.children].forEach((c) => c.classList.add('w-100'));
    innerRow.prepend(this._createDeleteBtn(formRow));
    innerRow.classList.add('flex', 'items-center', 'gap-2');

    formRow.classList.remove('form-row');
    formRow.classList.add('mb-2');
    [...formRow.querySelectorAll('.form-row')].forEach((c) => c.classList.remove('form-row'))
  }

  _createDeleteBtn(formRow) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.classList.add('btn-link', 'btn-small', 'btn-icon');
    btn.innerHTML = '<i class="ph ph-x"></i>';

    btn.addEventListener('click', () => {
      formRow.parentElement.removeChild(formRow);
    });
    return btn;
  }
}
