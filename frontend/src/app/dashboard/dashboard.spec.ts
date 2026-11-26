import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { DashboardComponent } from './dashboard';
import { ApiService } from '../services/api';

describe('DashboardComponent', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DashboardComponent],
      providers: [
        provideRouter([]),
        {
          provide: ApiService,
          useValue: {
            getCarros: () => of([]),
            getStats: () => of({ total: 0, disponiveis: 0, vendidos: 0, por_condicao: { novo: 0, seminovo: 0, usado: 0, velho: 0, indefinida: 0 } }),
            getAuxiliares: () => of([]),
          },
        },
      ],
    }).compileComponents();
  });

  it('combina filtros de quilometragem, ano e classificação', () => {
    const fixture = TestBed.createComponent(DashboardComponent);
    const componente = fixture.componentInstance;
    componente.carros = [
      { id_carro: 1, placa: 'ABC1D23', chassi: 'CHASSI1', modelo: 'Civic', marca: 'Honda', cor: 'Preto', ano: 2024, quilometragem: 10000, situacao: 'D', condicao: 'seminovo', id_marca: 2 },
      { id_carro: 2, placa: 'DEF2B32', chassi: 'CHASSI2', modelo: 'Uno', marca: 'Fiat', cor: 'Vermelho', ano: 2008, quilometragem: 180000, situacao: 'V', condicao: 'velho', id_marca: 4 },
    ];
    componente.filtro.kmMax = 30000;
    componente.filtro.anoMin = 2020;
    componente.filtro.condicao = 'seminovo';
    componente.aplicarFiltros();
    expect(componente.carrosFiltrados.map((carro) => carro.id_carro)).toEqual([1]);
  });
});
