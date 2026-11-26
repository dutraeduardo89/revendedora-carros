import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { RegisterComponent } from './register';
import { ApiService } from '../services/api';

describe('RegisterComponent', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [RegisterComponent],
      providers: [provideRouter([]), { provide: ApiService, useValue: {} }],
    }).compileComponents();
  });

  it('exige senha com pelo menos 6 caracteres', () => {
    const fixture = TestBed.createComponent(RegisterComponent);
    const componente = fixture.componentInstance;
    componente.dados = { login: 'loja', senha: '123', confirmar: '123' };
    componente.registrar();
    expect(componente.erros['senha']).toContain('6');
  });
});
