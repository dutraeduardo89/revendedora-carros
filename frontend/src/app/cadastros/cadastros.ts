import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { ApiService } from '../services/api';

@Component({
  selector: 'app-cadastros',
  standalone: true,
  imports: [FormsModule, CommonModule],
  templateUrl: './cadastros.html'
})
export class CadastrosComponent implements OnInit {
  marcas: any[] = []; modelos: any[] = []; acessorios: any[] = [];
  
  filtroMarca = ''; filtroModelo = ''; filtroAcessorio = '';

  novaMarca = { nome: '' };
  novoModelo = { nome: '', id_marca: null };
  novoAcessorio = { nome: '' };
  
  msg = ''; tipoMsg = '';

  constructor(private api: ApiService, private cdr: ChangeDetectorRef) {}

  ngOnInit() { this.carregarTudo(); }

  carregarTudo() {
    this.api.getAuxiliares('marcas').subscribe(res => { this.marcas = res; this.cdr.detectChanges(); });
    this.api.getAuxiliares('modelos').subscribe(res => { this.modelos = res; this.cdr.detectChanges(); });
    this.api.getAuxiliares('acessorios').subscribe(res => { this.acessorios = res; this.cdr.detectChanges(); });
  }

  filtrarM(lista: any[], termo: string, campos: string[]) {
    if (!termo) return lista;
    const t = termo.toLowerCase();
    return lista.filter(item => campos.some(c => String(item[c]).toLowerCase().includes(t)));
  }

  salvar(tipo: string, obj: any) {
    // Agora só faz POST (Cadastrar)
    this.api.cadastrarAuxiliar(tipo, obj).subscribe({
      next: (res: any) => {
        this.msg = res.message; this.tipoMsg = 'alert-success';
        obj.nome = ''; if(tipo === 'modelo') obj.id_marca = null;
        this.carregarTudo();
      },
      error: (err: any) => { 
        this.msg = err.error.message; this.tipoMsg = 'alert-danger'; 
        this.cdr.detectChanges();
      }
    });
  }

  excluir(tipo: string, id: number) {
    if(confirm('Tem certeza que deseja excluir?')) {
      this.api.excluirAuxiliar(tipo, id).subscribe({
        next: (res: any) => { 
          this.msg = res.message; this.tipoMsg = 'alert-success'; 
          this.carregarTudo(); 
        },
        error: (err: any) => { 
          this.msg = err.error.message; this.tipoMsg = 'alert-danger'; 
          this.cdr.detectChanges();
        }
      });
    }
  }
}
