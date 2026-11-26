import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { CadastrosComponent } from './cadastros';
import { ApiService } from '../services/api';

describe('CadastrosComponent', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CadastrosComponent],
      providers: [
        provideRouter([]),
        {
          provide: ApiService,
          useValue: {
            getAuxiliares: () => of([]),
          },
        },
      ],
    }).compileComponents();
  });

  it('filtra por nome', () => {
    const fixture = TestBed.createComponent(CadastrosComponent);
    const componente = fixture.componentInstance;
    const lista = [{ id_cor: 1, nome: 'Azul' }, { id_cor: 2, nome: 'Verde' }];
    expect(componente.filtrar(lista, 'azu', ['nome']).length).toBe(1);
  });

  it('ordena por nome e por id', () => {
    const fixture = TestBed.createComponent(CadastrosComponent);
    const componente = fixture.componentInstance;
    componente.secao = 'cores';
    const lista = [{ id_cor: 10, nome: 'Verde' }, { id_cor: 2, nome: 'Azul' }];
    componente.ordem = 'nome-asc';
    expect(componente.ordenar(lista).map((item) => item.nome)).toEqual(['Azul', 'Verde']);
    componente.ordem = 'id-desc';
    expect(componente.ordenar(lista).map((item) => item.id_cor)).toEqual([10, 2]);
  });
});
