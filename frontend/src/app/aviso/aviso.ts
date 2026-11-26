import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-aviso',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './aviso.html',
})
export class AvisoComponent {
  @Input() titulo = '';
  @Input() tipo: 'warning' | 'danger' | 'success' = 'warning';
  @Input() contagem: number | null = null;
}
