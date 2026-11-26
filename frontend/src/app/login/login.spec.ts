import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { LoginComponent } from './login';
import { ApiService } from '../services/api';

describe('LoginComponent', () => {
  beforeEach(async () => {
    localStorage.clear();
    await TestBed.configureTestingModule({
      imports: [LoginComponent],
      providers: [provideRouter([]), { provide: ApiService, useValue: { login: () => undefined } }],
    }).compileComponents();
  });

  it('exige usuário e senha', () => {
    const fixture = TestBed.createComponent(LoginComponent);
    const componente = fixture.componentInstance;
    componente.entrar();
    expect(componente.erros['login']).toBeTruthy();
    expect(componente.erros['senha']).toBeTruthy();
  });
});
