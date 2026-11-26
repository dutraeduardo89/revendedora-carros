import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { CarFormComponent } from './car-form';
import { ApiService } from '../services/api';
import { REGRAS_PADRAO } from '../domain/vehicle-rules';

describe('CarFormComponent', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CarFormComponent],
      providers: [
        provideRouter([]),
        { provide: ActivatedRoute, useValue: { snapshot: { queryParamMap: convertToParamMap({}) } } },
        {
          provide: ApiService,
          useValue: {
            getAuxiliares: () => of([]),
            getRegras: () => of(REGRAS_PADRAO),
          },
        },
      ],
    }).compileComponents();
  });

  it('não envia o formulário vazio', () => {
    const fixture = TestBed.createComponent(CarFormComponent);
    const componente = fixture.componentInstance;
    componente.regras = REGRAS_PADRAO;
    componente.salvar();
    expect(componente.erros['placa']).toBeTruthy();
    expect(componente.erros['ano_fabricacao']).toBeTruthy();
    expect(componente.erros['quilometragem']).toBeTruthy();
    expect(componente.salvando).toBe(false);
  });
});
